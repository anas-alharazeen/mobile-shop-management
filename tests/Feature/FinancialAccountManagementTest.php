<?php

namespace Tests\Feature;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_account_can_be_created_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $logo = UploadedFile::fake()->image('bank-logo.png', 240, 240);

        $response = $this->actingAs($user)->post(route('finance.store-account'), [
            'name' => 'حساب البنك الرئيسي',
            'type' => 'bank',
            'opening_balance' => 1500,
            'description' => 'الحساب الرئيسي للتحويلات',
            'is_active' => true,
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('finance.index'));

        $account = FinancialAccount::query()->where('name', 'حساب البنك الرئيسي')->firstOrFail();

        $this->assertNotNull($account->logo_path);
        Storage::disk('public')->assertExists($account->logo_path);
        $this->assertSame('bank', $account->type->value);
        $this->assertSame('1500.00', $account->opening_balance);
        $this->assertSame('1500.00', $account->current_balance);
    }

    public function test_account_structure_is_not_changed_after_financial_history_but_logo_and_name_can_be_updated(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('financial-accounts/logos/old.png', 'old-logo');

        $account = FinancialAccount::query()->create([
            'name' => 'الصندوق',
            'type' => 'cash',
            'opening_balance' => 100,
            'current_balance' => 150,
            'description' => null,
            'logo_path' => 'financial-accounts/logos/old.png',
            'is_active' => true,
        ]);

        FinancialTransaction::query()->create([
            'financial_account_id' => $account->id,
            'type' => TransactionType::MANUAL_DEPOSIT->value,
            'direction' => TransactionDirection::INFLOW->value,
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
            'transaction_date' => now(),
            'description' => 'اختبار',
        ]);

        $newLogo = UploadedFile::fake()->image('new-logo.png', 240, 240);

        $response = $this->actingAs($user)->post(route('finance.update-account', $account), [
            'name' => 'الصندوق الرئيسي',
            'type' => 'bank',
            'opening_balance' => 9999,
            'description' => 'تم تحديث الاسم والشعار فقط',
            'is_active' => true,
            'logo' => $newLogo,
            'remove_logo' => false,
        ]);

        $response->assertRedirect(route('finance.index'));

        $account->refresh();

        $this->assertSame('الصندوق الرئيسي', $account->name);
        $this->assertSame('cash', $account->type->value);
        $this->assertSame('100.00', $account->opening_balance);
        $this->assertSame('150.00', $account->current_balance);
        $this->assertNotSame('financial-accounts/logos/old.png', $account->logo_path);
        Storage::disk('public')->assertMissing('financial-accounts/logos/old.png');
        Storage::disk('public')->assertExists($account->logo_path);
    }

    public function test_account_logo_can_be_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('financial-accounts/logos/bank.png', 'logo');

        $account = FinancialAccount::query()->create([
            'name' => 'البنك',
            'type' => 'bank',
            'opening_balance' => 0,
            'current_balance' => 0,
            'logo_path' => 'financial-accounts/logos/bank.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('finance.update-account', $account), [
            'name' => 'البنك',
            'type' => 'bank',
            'opening_balance' => 0,
            'description' => '',
            'is_active' => true,
            'remove_logo' => true,
        ]);

        $response->assertRedirect(route('finance.index'));

        $account->refresh();
        $this->assertNull($account->logo_path);
        Storage::disk('public')->assertMissing('financial-accounts/logos/bank.png');
    }

    public function test_expense_create_page_returns_active_categories_and_accounts(): void
    {
        $user = User::factory()->create();

        ExpenseCategory::query()->create([
            'name' => 'كهرباء',
            'is_active' => true,
        ]);

        FinancialAccount::query()->create([
            'name' => 'كاش',
            'type' => 'cash',
            'opening_balance' => 500,
            'current_balance' => 500,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('finance.expenses.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/ExpenseCreate')
                ->has('categories', 1)
                ->has('accounts', 1)
                ->where('accounts.0.name', 'كاش')
            );
    }
}
