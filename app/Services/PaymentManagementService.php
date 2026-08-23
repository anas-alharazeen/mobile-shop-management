<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\RepairOrderStatus;
use App\Enums\SalesInvoiceStatus;
use App\Models\PurchaseInvoice;
use App\Models\RepairOrder;
use App\Models\RepairPayment;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\PurchasePayment;

class PaymentManagementService
{
    public function __construct(
        private readonly SalesInvoiceService $salesService,
        private readonly PurchaseInvoiceService $purchaseService,
        private readonly RepairOrderService $repairService,
        private readonly FinancialFlowSummaryService $flowSummaryService
    ) {
    }

    public function addSalesPayment(SalesInvoice $invoice, array $data)
    {
        $invoice->refresh();

        if ($invoice->status !== SalesInvoiceStatus::APPROVED) {
            throw new \RuntimeException(
                'يمكن تسجيل التحصيل من مركز الدفعات للفواتير المعتمدة فقط.'
            );
        }

        if (
            $invoice->payment_status === PaymentStatus::PAID
            || (float) $invoice->remaining_amount <= 0
        ) {
            throw new \RuntimeException('الفاتورة مدفوعة بالكامل.');
        }

        return $this->salesService->addPayment($invoice, $data);
    }

    public function addPurchasePayment(PurchaseInvoice $invoice, array $data)
    {
        $invoice->refresh();

        if ($invoice->status !== PurchaseInvoiceStatus::APPROVED) {
            throw new \RuntimeException(
                'يمكن تسجيل دفعة المورد من مركز الدفعات للفواتير المعتمدة فقط.'
            );
        }

        if (
            $invoice->payment_status === PaymentStatus::PAID
            || (float) $invoice->remaining_amount <= 0
        ) {
            throw new \RuntimeException('الفاتورة مدفوعة بالكامل.');
        }

        return $this->purchaseService->addPayment($invoice, $data);
    }

    public function addRepairPayment(RepairOrder $order, array $data)
    {
        $order->refresh();

        if ($order->status === RepairOrderStatus::CANCELLED) {
            throw new \RuntimeException('لا يمكن تسجيل دفعة لطلب ملغى.');
        }

        if (
            $order->payment_status === PaymentStatus::PAID
            || (float) $order->remaining_amount <= 0
        ) {
            throw new \RuntimeException('طلب الصيانة مدفوع بالكامل.');
        }

        return $this->repairService->addPayment($order, $data);
    }

    public function getCustomerReceivables(): array
    {
        $sales = (float) SalesInvoice::query()
            ->where('status', SalesInvoiceStatus::APPROVED->value)
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        $repairs = (float) RepairOrder::query()
            ->where('status', '!=', RepairOrderStatus::CANCELLED->value)
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        return ['sales' => $sales, 'repair' => $repairs, 'total' => $sales + $repairs];
    }

    public function getSupplierPayables(): float
    {
        return (float) PurchaseInvoice::query()
            ->where('status', PurchaseInvoiceStatus::APPROVED->value)
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');
    }

    public function getOverdueCustomerPayments(): array
    {
        $today = today()->toDateString();

        $sales = SalesInvoice::query()
            ->where('status', SalesInvoiceStatus::APPROVED->value)
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get();

        $repairs = RepairOrder::query()
            ->where('status', '!=', RepairOrderStatus::CANCELLED->value)
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get();

        return [
            'sales' => $sales,
            'repairs' => $repairs,
            'total_count' => $sales->count() + $repairs->count(),
            'total_amount' => (float) $sales->sum('remaining_amount') + (float) $repairs->sum('remaining_amount'),
        ];
    }

    public function getOverdueSupplierPayments(): array
    {
        $purchases = PurchaseInvoice::query()
            ->where('status', PurchaseInvoiceStatus::APPROVED->value)
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->get();

        return [
            'purchases' => $purchases,
            'total_count' => $purchases->count(),
            'total_amount' => (float) $purchases->sum('remaining_amount'),
        ];
    }

    public function getTodayStats(): array
    {
        $summary = $this->flowSummaryService->summarize(
            now()->startOfDay(),
            now()->endOfDay()
        );

        return [
            'collected' => (float) ($summary['total_collected'] ?? 0),
            'paid_to_suppliers' => (float) ($summary['paid_to_suppliers'] ?? 0),
            'net_cash_flow' => (float) ($summary['net_cash_flow'] ?? 0),
        ];
    }

    public function getStats(): array
    {
        $customerReceivables = $this->getCustomerReceivables();
        $supplierPayables = $this->getSupplierPayables();
        $overdueCustomers = $this->getOverdueCustomerPayments();
        $overdueSuppliers = $this->getOverdueSupplierPayments();
        $today = $this->getTodayStats();

        return [
            'customer_receivables' => $customerReceivables['total'],
            'supplier_payables' => $supplierPayables,
            'collected_today' => $today['collected'],
            'paid_to_suppliers_today' => $today['paid_to_suppliers'],
            'net_cash_flow_today' => $today['net_cash_flow'],
            'overdue_customers_count' => $overdueCustomers['total_count'],
            'overdue_customers_amount' => $overdueCustomers['total_amount'],
            'overdue_suppliers_count' => $overdueSuppliers['total_count'],
            'overdue_suppliers_amount' => $overdueSuppliers['total_amount'],
            'unpaid_invoices' => SalesInvoice::query()
                ->where('status', SalesInvoiceStatus::APPROVED->value)
                ->where('payment_status', PaymentStatus::UNPAID->value)
                ->count(),
            'partially_paid_invoices' => SalesInvoice::query()
                ->where('status', SalesInvoiceStatus::APPROVED->value)
                ->where('payment_status', PaymentStatus::PARTIALLY_PAID->value)
                ->count(),
        ];
    }
}
