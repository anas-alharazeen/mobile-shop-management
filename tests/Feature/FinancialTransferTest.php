<?php

namespace Tests\Feature;

use App\Enums\FinancialTransferStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransfer;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Services\FinancialFlowSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FinancialAccount $cashAccount;
    private FinancialAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->cashAccount = FinancialAccount::create([
            'name' => 'الصندوق الرئيسي',
            'type' => 'cash',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $this->bankAccount = FinancialAccount::create([
            'name' => 'الحساب البنكي',
            'type' => 'bank',
            'opening_balance' => 200,
            'current_balance' => 200,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_transfer_money_between_two_financial_accounts(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('finance.store-transfer'), [
                'from_account_id' => $this->cashAccount->id,
                'to_account_id' => $this->bankAccount->id,
                'amount' => 250,
                'transfer_date' => today()->toDateString(),
                'notes' => 'إيداع جزء من مبيعات اليوم',
            ]);

        $response->assertRedirect(route('finance.transfers'));
        $response->assertSessionHas('success');

        $this->assertEquals(750.00, (float) $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(450.00, (float) $this->bankAccount->fresh()->current_balance);

        $transfer = FinancialTransfer::query()->firstOrFail();

        $this->assertSame(FinancialTransferStatus::POSTED, $transfer->status);
        $this->assertEquals(250.00, (float) $transfer->amount);
        $this->assertSame($this->cashAccount->id, $transfer->from_account_id);
        $this->assertSame($this->bankAccount->id, $transfer->to_account_id);

        $this->assertDatabaseHas('financial_transactions', [
            'financial_account_id' => $this->cashAccount->id,
            'type' => TransactionType::ACCOUNT_TRANSFER->value,
            'direction' => TransactionDirection::OUTFLOW->value,
            'amount' => 250,
            'reference_type' => $transfer->getMorphClass(),
            'reference_id' => $transfer->id,
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'financial_account_id' => $this->bankAccount->id,
            'type' => TransactionType::ACCOUNT_TRANSFER->value,
            'direction' => TransactionDirection::INFLOW->value,
            'amount' => 250,
            'reference_type' => $transfer->getMorphClass(),
            'reference_id' => $transfer->id,
        ]);

        $this->assertSame(2, $transfer->transactions()->count());
    }

    /** @test */
    public function transfer_cannot_use_the_same_account_as_source_and_destination(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('finance.transfers'))
            ->post(route('finance.store-transfer'), [
                'from_account_id' => $this->cashAccount->id,
                'to_account_id' => $this->cashAccount->id,
                'amount' => 100,
                'transfer_date' => today()->toDateString(),
            ]);

        $response->assertRedirect(route('finance.transfers'));
        $response->assertSessionHasErrors('to_account_id');

        $this->assertDatabaseCount('financial_transfers', 0);
        $this->assertEquals(1000.00, (float) $this->cashAccount->fresh()->current_balance);
    }

    /** @test */
    public function transfer_is_rejected_when_source_balance_is_insufficient(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('finance.transfers'))
            ->post(route('finance.store-transfer'), [
                'from_account_id' => $this->cashAccount->id,
                'to_account_id' => $this->bankAccount->id,
                'amount' => 1500,
                'transfer_date' => today()->toDateString(),
            ]);

        $response->assertRedirect(route('finance.transfers'));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('financial_transfers', 0);
        $this->assertDatabaseCount('financial_transactions', 0);
        $this->assertEquals(1000.00, (float) $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(200.00, (float) $this->bankAccount->fresh()->current_balance);
    }

    /** @test */
    public function cancelling_a_transfer_creates_reversal_movements_and_restores_balances(): void
    {
        $this->actingAs($this->user)
            ->post(route('finance.store-transfer'), [
                'from_account_id' => $this->cashAccount->id,
                'to_account_id' => $this->bankAccount->id,
                'amount' => 300,
                'transfer_date' => today()->toDateString(),
            ]);

        $transfer = FinancialTransfer::query()->firstOrFail();

        $response = $this->actingAs($this->user)
            ->post(route('finance.cancel-transfer', $transfer), [
                'reason' => 'تم اختيار الحساب البنكي الخطأ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $transfer->refresh();

        $this->assertSame(FinancialTransferStatus::CANCELLED, $transfer->status);
        $this->assertNotNull($transfer->cancelled_at);
        $this->assertSame('تم اختيار الحساب البنكي الخطأ', $transfer->cancellation_reason);

        $this->assertEquals(1000.00, (float) $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(200.00, (float) $this->bankAccount->fresh()->current_balance);

        $originalTransactions = FinancialTransaction::query()
            ->where('type', TransactionType::ACCOUNT_TRANSFER->value)
            ->get();

        $this->assertCount(2, $originalTransactions);

        foreach ($originalTransactions as $originalTransaction) {
            $this->assertDatabaseHas('financial_transactions', [
                'type' => TransactionType::REVERSAL->value,
                'reference_type' => $originalTransaction->getMorphClass(),
                'reference_id' => $originalTransaction->id,
                'amount' => 300,
            ]);
        }
    }

    /** @test */
    public function internal_transfers_do_not_inflate_store_level_cash_flow_totals(): void
    {
        $this->actingAs($this->user)
            ->post(route('finance.store-transfer'), [
                'from_account_id' => $this->cashAccount->id,
                'to_account_id' => $this->bankAccount->id,
                'amount' => 400,
                'transfer_date' => today()->toDateString(),
            ]);

        $summary = app(FinancialFlowSummaryService::class)->summarize(
            today()->startOfDay(),
            today()->endOfDay()
        );

        $this->assertEquals(0.00, $summary['total_inflows']);
        $this->assertEquals(0.00, $summary['total_outflows']);
        $this->assertEquals(0.00, $summary['net_cash_flow']);
        $this->assertEquals(0.00, $summary['other_net_flow']);
    }
}
