<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\PurchasePayment;
use App\Models\RepairPayment;
use App\Models\SalesPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialAccountService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function createAccount(array $data): FinancialAccount
    {
        return FinancialAccount::create([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateAccount(FinancialAccount $account, array $data): FinancialAccount
    {
        if ((array_key_exists('current_balance', $data) || array_key_exists('opening_balance', $data))
            && $account->transactions()->exists()
        ) {
            unset($data['current_balance'], $data['opening_balance']);
        }

        $account->update($data);

        return $account->fresh();
    }

    public function setActiveStatus(FinancialAccount $account,bool $isActive): FinancialAccount {
        return DB::transaction(function () use ($account, $isActive) {
            $lockedAccount = FinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAccount->update([
                'is_active' => $isActive,
            ]);

            return $lockedAccount->fresh();
        });
    }

    public function resolveAccountForPayment(string|PaymentMethod $method, ?int $accountId = null): FinancialAccount
    {
        $paymentMethod = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);
        $expectedType = match ($paymentMethod) {
            PaymentMethod::CASH => AccountType::CASH,
            PaymentMethod::BANK_TRANSFER => AccountType::BANK,
            PaymentMethod::BANKING_APP => AccountType::BANKING_APP,
            PaymentMethod::EXCHANGE_CREDIT => throw new \RuntimeException('رصيد الاستبدال لا يرتبط بحساب مالي.'),
        };

        if ($accountId) {
            $account = FinancialAccount::query()->active()->findOrFail($accountId);
            if ($account->type !== $expectedType) {
                throw new \RuntimeException('الحساب المالي المختار لا يتوافق مع طريقة الدفع.');
            }

            return $account;
        }

        if ($paymentMethod === PaymentMethod::CASH) {
            $defaultId = $this->settings->get('default_cash_account');
            if ($defaultId) {
                $default = FinancialAccount::query()->active()->find($defaultId);
                if ($default && $default->type === AccountType::CASH) {
                    return $default;
                }
            }
        }

        $account = FinancialAccount::query()
            ->active()
            ->where('type', $expectedType->value)
            ->orderBy('id')
            ->first();

        if (!$account) {
            throw new \RuntimeException(match ($paymentMethod) {
                PaymentMethod::CASH => 'لا يوجد صندوق كاش نشط. أضف حساباً مالياً من قسم الصندوق.',
                PaymentMethod::BANK_TRANSFER => 'لا يوجد حساب بنكي نشط. أضف حساباً بنكياً من قسم الصندوق.',
                PaymentMethod::BANKING_APP => 'لا يوجد حساب تطبيق بنكي نشط. أضف الحساب من قسم الصندوق.',
                PaymentMethod::EXCHANGE_CREDIT => 'رصيد الاستبدال لا يحتاج إلى حساب مالي.',
            });
        }

        return $account;
    }

    public function addInflow(
        FinancialAccount $account,
        TransactionType $type,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?string $notes = null,
        mixed $transactionDate = null
    ): FinancialTransaction {
        return $this->recordTransaction(
            $account,
            $type,
            TransactionDirection::INFLOW,
            $amount,
            $description,
            $reference,
            $notes,
            $transactionDate
        );
    }

    public function addOutflow(
        FinancialAccount $account,
        TransactionType $type,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?string $notes = null,
        mixed $transactionDate = null
    ): FinancialTransaction {
        return $this->recordTransaction(
            $account,
            $type,
            TransactionDirection::OUTFLOW,
            $amount,
            $description,
            $reference,
            $notes,
            $transactionDate
        );
    }

    private function recordTransaction(
        FinancialAccount $account,
        TransactionType $type,
        TransactionDirection $direction,
        float $amount,
        string $description,
        ?Model $reference,
        ?string $notes,
        mixed $transactionDate
    ): FinancialTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($account, $type, $direction, $amount, $description, $reference, $notes, $transactionDate) {
            $lockedAccount = FinancialAccount::query()->lockForUpdate()->findOrFail($account->id);
            if (!$lockedAccount->is_active) {
                throw new \RuntimeException('الحساب المالي غير نشط.');
            }

            if ($reference) {
                $existing = FinancialTransaction::query()
                    ->where('reference_type', $reference::class)
                    ->where('reference_id', $reference->getKey())
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $balanceBefore = (float) $lockedAccount->current_balance;
            if ($direction === TransactionDirection::OUTFLOW && $balanceBefore < $amount) {
                throw new \RuntimeException('الرصيد غير كافٍ. المتوفر: ' . number_format($balanceBefore, 2) . ' شيكل');
            }

            $balanceAfter = $direction === TransactionDirection::INFLOW
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            $transaction = FinancialTransaction::create([
                'financial_account_id' => $lockedAccount->id,
                'type' => $type->value,
                'direction' => $direction->value,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $transactionDate ?? now(),
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'notes' => $notes,
            ]);

            $lockedAccount->update(['current_balance' => $balanceAfter]);

            return $transaction;
        });
    }

    public function reverseTransaction(FinancialTransaction $transaction, string $reason): FinancialTransaction
    {
        $existing = FinancialTransaction::query()
            ->where('reference_type', $transaction->getMorphClass())
            ->where('reference_id', $transaction->id)
            ->where('type', TransactionType::REVERSAL->value)
            ->first();

        if ($existing) {
            return $existing;
        }

        $account = $transaction->account;
        $description = "عكس حركة: {$transaction->description}";
        $notes = "سبب العكس: {$reason}";

        return $transaction->direction === TransactionDirection::INFLOW
            ? $this->addOutflow($account, TransactionType::REVERSAL, (float) $transaction->amount, $description, $transaction, $notes)
            : $this->addInflow($account, TransactionType::REVERSAL, (float) $transaction->amount, $description, $transaction, $notes);
    }

    public function linkPaymentToAccount(
        SalesPayment|PurchasePayment|RepairPayment $payment,
        FinancialAccount $account,
        string $type,
        string $description
    ): FinancialTransaction {
        $existing = FinancialTransaction::query()
            ->where('reference_type', $payment->getMorphClass())
            ->where('reference_id', $payment->id)
            ->first();

        if ($existing) {
            if (!$payment->financial_account_id) {
                $payment->forceFill(['financial_account_id' => $existing->financial_account_id])->save();
            }

            return $existing;
        }

        $transactionType = match ($type) {
            'sale' => TransactionType::SALE_PAYMENT,
            'repair' => TransactionType::REPAIR_PAYMENT,
            'purchase' => TransactionType::PURCHASE_PAYMENT,
            default => throw new \RuntimeException('نوع الدفعة غير معروف.'),
        };

        $payment->forceFill(['financial_account_id' => $account->id])->save();

        return in_array($type, ['sale', 'repair'], true)
            ? $this->addInflow($account, $transactionType, (float) $payment->amount, $description, $payment, $payment->notes, $payment->paid_at)
            : $this->addOutflow($account, $transactionType, (float) $payment->amount, $description, $payment, $payment->notes, $payment->paid_at);
    }

    public function linkPaymentAutomatically(
        SalesPayment|PurchasePayment|RepairPayment $payment,
        string $type,
        string $description,
        ?int $accountId = null
    ): FinancialTransaction {
        $account = $this->resolveAccountForPayment($payment->payment_method, $accountId ?? $payment->financial_account_id);

        return $this->linkPaymentToAccount($payment, $account, $type, $description);
    }

    public function syncPastPayments(?FinancialAccount $fallbackCashAccount = null): array
    {
        $synced = 0;
        $skipped = 0;
        $errors = [];

        $groups = [
            [
                SalesPayment::query()->whereHas('invoice', fn($q) => $q->where('status', 'approved'))->with('invoice')->get(),
                'sale',
                fn($payment) => "دفعة مبيعات - {$payment->invoice->invoice_number}",
            ],
            [
                RepairPayment::query()->with('repairOrder')->get(),
                'repair',
                fn($payment) => "دفعة صيانة - {$payment->repairOrder->order_number}",
            ],
            [
                PurchasePayment::query()->whereHas('invoice', fn($q) => $q->where('status', 'approved'))->with('invoice')->get(),
                'purchase',
                fn($payment) => "دفعة مشتريات - {$payment->invoice->invoice_number}",
            ],
        ];

        foreach ($groups as [$payments, $type, $description]) {
            foreach ($payments as $payment) {
                try {
                    $alreadyLinked = FinancialTransaction::query()
                        ->where('reference_type', $payment->getMorphClass())
                        ->where('reference_id', $payment->id)
                        ->exists();
                    if ($alreadyLinked) {
                        $skipped++;
                        continue;
                    }

                    $account = $payment->financial_account_id
                        ? FinancialAccount::query()->active()->find($payment->financial_account_id)
                        : null;

                    if (!$account && $fallbackCashAccount && $payment->payment_method === PaymentMethod::CASH) {
                        $account = $fallbackCashAccount;
                    }

                    $account ??= $this->resolveAccountForPayment($payment->payment_method);
                    $this->linkPaymentToAccount($payment, $account, $type, $description($payment));
                    $synced++;
                } catch (\Throwable $exception) {
                    $errors[] = [
                        'payment' => $payment::class . '#' . $payment->id,
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "تمت مزامنة {$synced} دفعة، وتجاوز {$skipped} دفعة مرتبطة مسبقاً.",
        ];
    }
}
