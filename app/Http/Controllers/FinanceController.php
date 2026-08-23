<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\DailyAccountClosing;
use App\Services\FinancialAccountService;
use App\Services\FinancialFlowSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Services\SettingsService;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinancialAccountService $financeService,
        private readonly SettingsService $settings,
        private readonly FinancialFlowSummaryService $financialFlowSummary,
    ) {
    }

    /**
     * المركز المالي - نظرة عامة
     */
    public function index()
    {
        $accounts = FinancialAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $accounts->each(function (FinancialAccount $account): void {
            $account->setAttribute('type_label', $account->type_label);
            $account->setAttribute('today_inflows', $account->today_inflows);
            $account->setAttribute('today_outflows', $account->today_outflows);
        });

        $activeAccounts =
            $accounts->where(
                'is_active',
                true
            );

        $todayFlow =
            $this
                ->financialFlowSummary
                ->summarize(
                    today()->startOfDay(),
                    today()->endOfDay()
                );

        /*
         * تعطيل الحساب يمنع استخدامه في عمليات جديدة فقط.
         * رصيده يبقى جزءاً من أموال المحل، لذلك إجمالي الأرصدة
         * يجمع الحسابات النشطة وغير النشطة.
         */
        $stats = [
            'total_balance' =>
                $accounts->sum(
                    'current_balance'
                ),

            'cash_balance' =>
                $accounts
                    ->filter(
                        fn (FinancialAccount $account) =>
                            $account->type?->value
                            === 'cash'
                    )
                    ->sum(
                        'current_balance'
                    ),

            'bank_balance' =>
                $accounts
                    ->filter(
                        fn (FinancialAccount $account) =>
                            $account->type?->value
                            === 'bank'
                    )
                    ->sum(
                        'current_balance'
                    ),

            'app_balance' =>
                $accounts
                    ->filter(
                        fn (FinancialAccount $account) =>
                            $account->type?->value
                            === 'banking_app'
                    )
                    ->sum(
                        'current_balance'
                    ),

            'today_inflows' =>
                (float) $todayFlow[
                    'total_inflows'
                ],

            'today_outflows' =>
                (float) $todayFlow[
                    'total_outflows'
                ],

            'today_expenses' =>
                (float) $todayFlow[
                    'expenses'
                ],

            'today_net_flow' =>
                (float) $todayFlow[
                    'net_cash_flow'
                ],

            'active_accounts' =>
                $activeAccounts->count(),

            'inactive_accounts' =>
                $accounts
                    ->where(
                        'is_active',
                        false
                    )
                    ->count(),
        ];

        return Inertia::render('Finance/Index', [
            'accounts' => $accounts,
            'stats' => $stats,
            'accountTypes' => AccountType::labels(),
        ]);
    }

    /**
     * إضافة حساب مالي
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,banking_app'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['opening_balance'] = (float) ($validated['opening_balance'] ?? 0);
        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $this->financeService->createAccount($validated);

        return redirect()
            ->route('finance.index')
            ->with('success', 'تم إضافة الحساب بنجاح.');
    }

    public function toggleAccountStatus(
        Request $request,
        FinancialAccount $financialAccount
    ) {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $account = $this->financeService->setActiveStatus(
                $financialAccount,
                (bool) $validated['is_active']
            );

            $message = $account->is_active
                ? 'تم تفعيل الحساب بنجاح.'
                : 'تم تعطيل الحساب للعمليات الجديدة مع الاحتفاظ بجميع حركاته.';

            return redirect()
                ->back()
                ->with('success', $message);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * تفاصيل الحساب المالي
     */
    public function showAccount(
    Request $request,
    FinancialAccount $financialAccount
) {
    $transactionsQuery = $this->accountTransactionsQuery(
        $request,
        $financialAccount
    );

    $periodQuery = $financialAccount->transactions()
        ->when($request->filled('start_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        })
        ->when($request->filled('end_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        });

    $periodOpeningBalance = $request->filled('start_date')
        ? $financialAccount->getBalanceBeforeDate($request->start_date)
        : (float) $financialAccount->opening_balance;

    $periodInflows = (float) (clone $periodQuery)
        ->where('direction', TransactionDirection::INFLOW->value)
        ->sum('amount');

    $periodOutflows = (float) (clone $periodQuery)
        ->where('direction', TransactionDirection::OUTFLOW->value)
        ->sum('amount');

    $filteredInflows = (float) (clone $transactionsQuery)
        ->where('direction', TransactionDirection::INFLOW->value)
        ->sum('amount');

    $filteredOutflows = (float) (clone $transactionsQuery)
        ->where('direction', TransactionDirection::OUTFLOW->value)
        ->sum('amount');

    $transactions = (clone $transactionsQuery)
        ->orderByDesc('transaction_date')
        ->orderByDesc('id')
        ->paginate(20)
        ->withQueryString();

    $financialAccount->setAttribute(
        'type_label',
        $financialAccount->type_label
    );

    return Inertia::render('Finance/AccountShow', [
        'account' => $financialAccount,
        'transactions' => $transactions,
        'stats' => [
            'period_opening_balance' => round($periodOpeningBalance, 2),
            'period_closing_balance' => round(
                $periodOpeningBalance + $periodInflows - $periodOutflows,
                2
            ),
            'total_inflows' => round($filteredInflows, 2),
            'total_outflows' => round($filteredOutflows, 2),
            'net_flow' => round($filteredInflows - $filteredOutflows, 2),
            'transaction_count' => (clone $transactionsQuery)->count(),
        ],
        'filters' => [
            'search' => $request->search,
            'type' => $request->type,
            'direction' => $request->direction,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ],
        'transactionTypes' => TransactionType::labels(),
        'directions' => TransactionDirection::labels(),
    ]);
}


    public function printAccount(
    Request $request,
    FinancialAccount $financialAccount
) {
    $transactionsQuery = $this->accountTransactionsQuery(
        $request,
        $financialAccount
    );

    $periodQuery = $financialAccount->transactions()
        ->when($request->filled('start_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        })
        ->when($request->filled('end_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        });

    $openingBalance = $request->filled('start_date')
        ? $financialAccount->getBalanceBeforeDate($request->start_date)
        : (float) $financialAccount->opening_balance;

    $periodInflows = (float) (clone $periodQuery)
        ->where('direction', TransactionDirection::INFLOW->value)
        ->sum('amount');

    $periodOutflows = (float) (clone $periodQuery)
        ->where('direction', TransactionDirection::OUTFLOW->value)
        ->sum('amount');

    $transactions = (clone $transactionsQuery)
        ->orderBy('transaction_date')
        ->orderBy('id')
        ->get();

    $filteredInflows = (float) $transactions
        ->filter(fn (FinancialTransaction $transaction) => (
            $transaction->direction?->value === TransactionDirection::INFLOW->value
        ))
        ->sum('amount');

    $filteredOutflows = (float) $transactions
        ->filter(fn (FinancialTransaction $transaction) => (
            $transaction->direction?->value === TransactionDirection::OUTFLOW->value
        ))
        ->sum('amount');

    $financialAccount->setAttribute(
        'type_label',
        $financialAccount->type_label
    );

    return Inertia::render('Finance/AccountPrint', [
        'store' => $this->settings->getStoreSettings(),
        'account' => $financialAccount,
        'transactions' => $transactions,
        'summary' => [
            'opening_balance' => round($openingBalance, 2),
            'total_inflows' => round($filteredInflows, 2),
            'total_outflows' => round($filteredOutflows, 2),
            'net_flow' => round($filteredInflows - $filteredOutflows, 2),
            'closing_balance' => round(
                $openingBalance + $periodInflows - $periodOutflows,
                2
            ),
            'transaction_count' => $transactions->count(),
            'generated_at' => now()->toIso8601String(),
        ],
        'filters' => [
            'search' => $request->search,
            'type' => $request->type,
            'direction' => $request->direction,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ],
        'transactionTypes' => TransactionType::labels(),
        'directions' => TransactionDirection::labels(),
    ]);
}


    /**
     * قائمة المصروفات
     */
    public function expenses(Request $request)
    {
        $query = Expense::with(['category', 'account'])
            ->search($request->search)
            ->status($request->status)
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('expense_category_id', $request->category_id);
            })
            ->when($request->account_id, function ($q) use ($request) {
                return $q->where('financial_account_id', $request->account_id);
            })
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('expense_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('expense_date', '<=', $request->end_date);
            });

        $expenses = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'today' => Expense::query()
                ->where('status', 'posted')
                ->whereDate('expense_date', today())
                ->sum('amount'),

            'month' => Expense::query()
                ->where('status', 'posted')
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount'),

            'top_category' => ExpenseCategory::query()
                ->whereHas('expenses', fn ($query) => $query->where('status', 'posted'))
                ->withCount([
                    'expenses as posted_expenses_count' =>
                        fn ($query) => $query->where('status', 'posted'),
                ])
                ->orderByDesc('posted_expenses_count')
                ->first()?->name,

            'count' => Expense::query()
                ->where('status', 'posted')
                ->count(),
        ];

        $categories = ExpenseCategory::active()->get(['id', 'name']);
        $accounts = FinancialAccount::active()->get([
            'id',
            'name',
            'type',
            'current_balance',
        ]);

        return Inertia::render('Finance/Expenses', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $categories,
            'accounts' => $accounts,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'category_id' => $request->category_id,
                'account_id' => $request->account_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    /**
     * إنشاء مصروف جديد
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id,is_active,1'],
            'financial_account_id' => ['required', 'exists:financial_accounts,id,is_active,1'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'beneficiary' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;

        try {
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('expenses', 'public');
            }

            DB::transaction(function () use ($validated, $attachmentPath): void {
                $account = FinancialAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($validated['financial_account_id']);

                $expense = Expense::create([
                    'expense_number' => Expense::generateNumber(),
                    'expense_category_id' => $validated['expense_category_id'],
                    'financial_account_id' => $validated['financial_account_id'],
                    'amount' => $validated['amount'],
                    'expense_date' => $validated['expense_date'],
                    'beneficiary' => $validated['beneficiary'] ?? null,
                    'description' => $validated['description'],
                    'attachment_path' => $attachmentPath,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'posted',
                ]);

                $this->financeService->addOutflow(
                    $account,
                    TransactionType::EXPENSE,
                    $validated['amount'],
                    $validated['description'],
                    $expense,
                    $validated['notes'] ?? null,
                    $validated['expense_date']
                );
            });
        } catch (\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            report($exception);

            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.expenses')->with('success', 'تم تسجيل المصروف بنجاح');
    }

    /**
     * إلغاء مصروف
     */
    public function cancelExpense(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($expense, $validated): void {
                $lockedExpense = Expense::query()->lockForUpdate()->findOrFail($expense->id);

                if (!$lockedExpense->can_be_cancelled) {
                    throw new \RuntimeException('لا يمكن إلغاء هذا المصروف');
                }

                $transaction = FinancialTransaction::query()
                    ->where('reference_type', $lockedExpense->getMorphClass())
                    ->where('reference_id', $lockedExpense->id)
                    ->where('type', '!=', TransactionType::REVERSAL->value)
                    ->first();

                if ($transaction) {
                    $this->financeService->reverseTransaction($transaction, $validated['reason']);
                }

                $lockedExpense->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $validated['reason'],
                ]);
            });
        } catch (\Throwable $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.expenses')->with('success', 'تم إلغاء المصروف بنجاح');
    }

    /**
     * الإيداع والسحب اليدوي
     */
    public function manualTransaction(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:financial_accounts,id,is_active,1'],
            'type' => ['required', 'string', 'in:deposit,withdrawal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $account = FinancialAccount::query()
                ->where('is_active', true)
                ->findOrFail($validated['account_id']);

            if ($validated['type'] === 'deposit') {
                $this->financeService->addInflow(
                    $account,
                    TransactionType::MANUAL_DEPOSIT,
                    $validated['amount'],
                    $validated['reason'],
                    null,
                    $validated['notes'] ?? null,
                    $validated['date']
                );

                $message = 'تم تسجيل الإيداع بنجاح.';
            } else {
                $this->financeService->addOutflow(
                    $account,
                    TransactionType::MANUAL_WITHDRAWAL,
                    $validated['amount'],
                    $validated['reason'],
                    null,
                    $validated['notes'] ?? null,
                    $validated['date']
                );

                $message = 'تم تسجيل السحب بنجاح.';
            }

            return redirect()
                ->route('finance.index')
                ->with('success', $message);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * الإغلاق اليومي
     */
    public function closing(Request $request)
    {
        $validated = $request->validate([
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'closing_date' => ['required', 'date'],
            'actual_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                $account = FinancialAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($validated['financial_account_id']);

                $exists = DailyAccountClosing::query()
                    ->where('financial_account_id', $account->id)
                    ->whereDate('closing_date', $validated['closing_date'])
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    throw new \RuntimeException('تم إغلاق هذا الحساب لهذا اليوم مسبقاً');
                }

                $date = $validated['closing_date'];
                $openingBalance = $account->getBalanceBeforeDate($date);
                $totalInflows = $account->transactions()
                    ->where('direction', 'inflow')
                    ->whereDate('transaction_date', $date)
                    ->sum('amount');
                $totalOutflows = $account->transactions()
                    ->where('direction', 'outflow')
                    ->whereDate('transaction_date', $date)
                    ->sum('amount');
                $expectedBalance = $openingBalance + $totalInflows - $totalOutflows;
                $actualBalance = (float) $validated['actual_balance'];
                $difference = $actualBalance - $expectedBalance;

                if (abs($difference) > 0.005 && blank($validated['notes'] ?? null)) {
                    throw new \RuntimeException('يجب كتابة ملاحظة عند وجود فرق في الإغلاق اليومي.');
                }

                DailyAccountClosing::create([
                    'financial_account_id' => $account->id,
                    'closing_date' => $date,
                    'opening_balance' => $openingBalance,
                    'total_inflows' => $totalInflows,
                    'total_outflows' => $totalOutflows,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $actualBalance,
                    'difference' => $difference,
                    'notes' => $validated['notes'] ?? null,
                    'closed_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.closings')->with('success', 'تم إغلاق اليوم بنجاح');
    }

    /**
     * عرض سجل الإغلاقات
     */
    public function closings(Request $request)
    {
        $query = DailyAccountClosing::with(['account'])
            ->when($request->account_id, function ($q) use ($request) {
                return $q->where('financial_account_id', $request->account_id);
            })
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('closing_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('closing_date', '<=', $request->end_date);
            });

        $closings = $query->orderBy('closing_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $accounts = FinancialAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'is_active',
            ]);

        return Inertia::render('Finance/Closings', [
            'closings' => $closings,
            'accounts' => $accounts,
            'filters' => [
                'account_id' => $request->account_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    /**
     * مزامنة الدفعات السابقة
     */
    public function syncPayments(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:financial_accounts,id,is_active,1'],
        ]);

        try {
            $account = FinancialAccount::query()
                ->where('is_active', true)
                ->findOrFail($validated['account_id']);

            $result = $this->financeService->syncPastPayments($account);

            return redirect()
                ->route('finance.index')
                ->with(
                    'success',
                    $result['message'] ?? 'تمت مزامنة الدفعات بنجاح.'
                );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        }
    }
    private function accountTransactionsQuery(
    Request $request,
    FinancialAccount $financialAccount
) {
    return $financialAccount->transactions()
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->search);

            $query->where(function ($nestedQuery) use ($search) {
                $nestedQuery
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        })
        ->when($request->filled('type'), function ($query) use ($request) {
            $query->where('type', $request->type);
        })
        ->when($request->filled('direction'), function ($query) use ($request) {
            $query->where('direction', $request->direction);
        })
        ->when($request->filled('start_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        })
        ->when($request->filled('end_date'), function ($query) use ($request) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        });
}

}
