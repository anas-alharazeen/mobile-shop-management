<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\FinancialTransferStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransfer;
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
            'logo_path' => $data['logo_path'] ?? null,
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

        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
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

    public function createTransfer(array $data): FinancialTransfer
    {
        $fromAccountId = (int) $data['from_account_id'];
        $toAccountId = (int) $data['to_account_id'];
        $amount = round((float) $data['amount'], 2);

        if ($fromAccountId === $toAccountId) {
            throw new \RuntimeException('لا يمكن التحويل إلى نفس الحساب.');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('مبلغ التحويل يجب أن يكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $data): FinancialTransfer {
            $accountIds = [$fromAccountId, $toAccountId];
            sort($accountIds, SORT_NUMERIC);

            $accounts = FinancialAccount::query()
                ->whereIn('id', $accountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromAccount = $accounts->get($fromAccountId);
            $toAccount = $accounts->get($toAccountId);

            if (!$fromAccount || !$toAccount) {
                throw new \RuntimeException('أحد الحسابات المالية غير موجود.');
            }

            if (!$fromAccount->is_active || !$toAccount->is_active) {
                throw new \RuntimeException('يجب أن يكون الحساب المصدر والحساب المستلم نشطين.');
            }

            $sourceBalanceBefore = round((float) $fromAccount->current_balance, 2);

            if ($sourceBalanceBefore < $amount) {
                throw new \RuntimeException(
                    'الرصيد غير كافٍ في حساب «'
                    . $fromAccount->name
                    . '». المتوفر: '
                    . number_format($sourceBalanceBefore, 2)
                    . ' شيكل'
                );
            }

            $destinationBalanceBefore = round((float) $toAccount->current_balance, 2);
            $sourceBalanceAfter = round($sourceBalanceBefore - $amount, 2);
            $destinationBalanceAfter = round($destinationBalanceBefore + $amount, 2);

            $transfer = FinancialTransfer::create([
                'transfer_number' => FinancialTransfer::generateNumber(),
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
                'status' => FinancialTransferStatus::POSTED->value,
            ]);

            FinancialTransaction::create([
                'financial_account_id' => $fromAccount->id,
                'type' => TransactionType::ACCOUNT_TRANSFER->value,
                'direction' => TransactionDirection::OUTFLOW->value,
                'amount' => $amount,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => $sourceBalanceAfter,
                'transaction_date' => $data['transfer_date'],
                'reference_type' => $transfer->getMorphClass(),
                'reference_id' => $transfer->id,
                'description' => "تحويل صادر {$transfer->transfer_number} إلى {$toAccount->name}",
                'notes' => $data['notes'] ?? null,
            ]);

            FinancialTransaction::create([
                'financial_account_id' => $toAccount->id,
                'type' => TransactionType::ACCOUNT_TRANSFER->value,
                'direction' => TransactionDirection::INFLOW->value,
                'amount' => $amount,
                'balance_before' => $destinationBalanceBefore,
                'balance_after' => $destinationBalanceAfter,
                'transaction_date' => $data['transfer_date'],
                'reference_type' => $transfer->getMorphClass(),
                'reference_id' => $transfer->id,
                'description' => "تحويل وارد {$transfer->transfer_number} من {$fromAccount->name}",
                'notes' => $data['notes'] ?? null,
            ]);

            $fromAccount->update([
                'current_balance' => $sourceBalanceAfter,
            ]);

            $toAccount->update([
                'current_balance' => $destinationBalanceAfter,
            ]);

            return $transfer->load(['fromAccount', 'toAccount', 'transactions.account']);
        });
    }

    public function cancelTransfer(FinancialTransfer $transfer, string $reason): FinancialTransfer
    {
        return DB::transaction(function () use ($transfer, $reason): FinancialTransfer {
            $lockedTransfer = FinancialTransfer::query()
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($lockedTransfer->status === FinancialTransferStatus::CANCELLED) {
                return $lockedTransfer->load(['fromAccount', 'toAccount', 'transactions.account']);
            }

            $accountIds = [
                (int) $lockedTransfer->from_account_id,
                (int) $lockedTransfer->to_account_id,
            ];
            sort($accountIds, SORT_NUMERIC);

            $accounts = FinancialAccount::query()
                ->whereIn('id', $accountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromAccount = $accounts->get((int) $lockedTransfer->from_account_id);
            $toAccount = $accounts->get((int) $lockedTransfer->to_account_id);

            if (!$fromAccount || !$toAccount) {
                throw new \RuntimeException('تعذر العثور على حسابات التحويل الأصلية.');
            }

            $originalTransactions = FinancialTransaction::query()
                ->where('reference_type', $lockedTransfer->getMorphClass())
                ->where('reference_id', $lockedTransfer->id)
                ->where('type', TransactionType::ACCOUNT_TRANSFER->value)
                ->lockForUpdate()
                ->get();

            $sourceTransaction = $originalTransactions->first(
                fn (FinancialTransaction $transaction): bool =>
                    (int) $transaction->financial_account_id === (int) $fromAccount->id
                    && $transaction->direction === TransactionDirection::OUTFLOW
            );

            $destinationTransaction = $originalTransactions->first(
                fn (FinancialTransaction $transaction): bool =>
                    (int) $transaction->financial_account_id === (int) $toAccount->id
                    && $transaction->direction === TransactionDirection::INFLOW
            );

            if (!$sourceTransaction || !$destinationTransaction) {
                throw new \RuntimeException('سجل التحويل المالي غير مكتمل، لذلك لا يمكن عكسه تلقائياً.');
            }

            $alreadyReversed = FinancialTransaction::query()
                ->where('type', TransactionType::REVERSAL->value)
                ->where(function ($query) use ($sourceTransaction, $destinationTransaction): void {
                    $query
                        ->where(function ($nested) use ($sourceTransaction): void {
                            $nested
                                ->where('reference_type', $sourceTransaction->getMorphClass())
                                ->where('reference_id', $sourceTransaction->id);
                        })
                        ->orWhere(function ($nested) use ($destinationTransaction): void {
                            $nested
                                ->where('reference_type', $destinationTransaction->getMorphClass())
                                ->where('reference_id', $destinationTransaction->id);
                        });
                })
                ->exists();

            if ($alreadyReversed) {
                throw new \RuntimeException('تم العثور على حركة عكس سابقة مرتبطة بهذا التحويل.');
            }

            $amount = round((float) $lockedTransfer->amount, 2);
            $destinationBalanceBefore = round((float) $toAccount->current_balance, 2);

            if ($destinationBalanceBefore < $amount) {
                throw new \RuntimeException(
                    'لا يمكن إلغاء التحويل لأن رصيد الحساب المستلم «'
                    . $toAccount->name
                    . '» أقل من مبلغ التحويل. المتوفر: '
                    . number_format($destinationBalanceBefore, 2)
                    . ' شيكل'
                );
            }

            $sourceBalanceBefore = round((float) $fromAccount->current_balance, 2);
            $sourceBalanceAfter = round($sourceBalanceBefore + $amount, 2);
            $destinationBalanceAfter = round($destinationBalanceBefore - $amount, 2);
            $reversalDate = now();
            $reversalNotes = 'سبب إلغاء التحويل: ' . trim($reason);

            FinancialTransaction::create([
                'financial_account_id' => $toAccount->id,
                'type' => TransactionType::REVERSAL->value,
                'direction' => TransactionDirection::OUTFLOW->value,
                'amount' => $amount,
                'balance_before' => $destinationBalanceBefore,
                'balance_after' => $destinationBalanceAfter,
                'transaction_date' => $reversalDate,
                'reference_type' => $destinationTransaction->getMorphClass(),
                'reference_id' => $destinationTransaction->id,
                'description' => "عكس تحويل {$lockedTransfer->transfer_number} - خصم من {$toAccount->name}",
                'notes' => $reversalNotes,
            ]);

            FinancialTransaction::create([
                'financial_account_id' => $fromAccount->id,
                'type' => TransactionType::REVERSAL->value,
                'direction' => TransactionDirection::INFLOW->value,
                'amount' => $amount,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => $sourceBalanceAfter,
                'transaction_date' => $reversalDate,
                'reference_type' => $sourceTransaction->getMorphClass(),
                'reference_id' => $sourceTransaction->id,
                'description' => "عكس تحويل {$lockedTransfer->transfer_number} - إعادة إلى {$fromAccount->name}",
                'notes' => $reversalNotes,
            ]);

            $toAccount->update([
                'current_balance' => $destinationBalanceAfter,
            ]);

            $fromAccount->update([
                'current_balance' => $sourceBalanceAfter,
            ]);

            $lockedTransfer->update([
                'status' => FinancialTransferStatus::CANCELLED->value,
                'cancelled_at' => $reversalDate,
                'cancellation_reason' => trim($reason),
            ]);

            return $lockedTransfer->fresh()->load(['fromAccount', 'toAccount', 'transactions.account']);
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
