<?php

namespace App\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ReportPresentationService
{
    public function title(string $type): string
    {
        return match ($type) {
            'sales' => 'تقرير المبيعات والأرباح',
            'purchases' => 'تقرير المشتريات',
            'inventory' => 'تقرير المخزون',
            'repairs' => 'تقرير الصيانة',
            'finance' => 'تقرير الدفعات والحسابات المالية',
            'returns' => 'تقرير المرتجعات والاستبدال',
            default => 'تقرير فنانة فون',
        };
    }

    public function present(string $type, array $data, array $filters = []): array
    {
        $summary = [];
        foreach ($this->summaryLabels($type) as $key => $label) {
            $summary[] = [
                'label' => $label,
                'value' => $this->formatValue($key, data_get($data, "summary.{$key}", 0)),
            ];
        }

        $sections = [];
        foreach ($this->sectionDefinitions($type) as $definition) {
            $source = data_get($data, $definition['key'], []);
            $rows = $this->normaliseRows($source, $definition['columns']);

            if ($rows !== []) {
                $sections[] = [
                    'title' => $definition['title'],
                    'columns' => array_values($definition['columns']),
                    'rows' => $rows,
                ];
            }
        }

        return [
            'type' => $type,
            'title' => $this->title($type),
            'summary' => $summary,
            'sections' => $sections,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i'),
        ];
    }

    public function excelRows(string $type, array $data, array $filters = []): array
    {
        $presentation = $this->present($type, $data, $filters);
        $rows = [
            [$presentation['title']],
            ['تاريخ الإنشاء', $presentation['generated_at']],
        ];

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $rows[] = [
                'الفترة',
                ($filters['start_date'] ?? 'البداية').' — '.($filters['end_date'] ?? 'اليوم'),
            ];
        }

        $rows[] = [];
        $rows[] = ['ملخص التقرير'];
        foreach ($presentation['summary'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        foreach ($presentation['sections'] as $section) {
            $rows[] = [];
            $rows[] = [$section['title']];
            $rows[] = $section['columns'];
            array_push($rows, ...$section['rows']);
        }

        return $rows;
    }

    private function summaryLabels(string $type): array
    {
        return match ($type) {
            'sales' => [
                'total_sales' => 'إجمالي المبيعات',
                'net_sales' => 'صافي المبيعات',
                'total_cost' => 'تكلفة المنتجات',
                'total_profit' => 'إجمالي الربح',
                'profit_margin' => 'هامش الربح',
                'total_discounts' => 'إجمالي الخصومات',
                'total_paid' => 'المبالغ المحصلة',
                'total_remaining' => 'المبالغ المتبقية',
                'total_returns' => 'مرتجعات المبيعات',
                'invoice_count' => 'عدد الفواتير',
                'average_invoice_value' => 'متوسط قيمة الفاتورة',
            ],
            'purchases' => [
                'total_purchases' => 'إجمالي المشتريات',
                'net_purchases' => 'صافي المشتريات',
                'total_paid' => 'المدفوع للموردين',
                'total_remaining' => 'المتبقي للموردين',
                'total_shipping' => 'تكاليف الشحن',
                'total_expenses' => 'المصاريف الإضافية',
                'total_returns' => 'مرتجعات المشتريات',
                'invoice_count' => 'عدد الفواتير',
            ],
            'inventory' => [
                'total_inventory_value' => 'إجمالي قيمة المخزون',
                'sales_warehouse_value' => 'قيمة مخزون المبيعات',
                'maintenance_warehouse_value' => 'قيمة مخزون الصيانة',
                'total_pieces' => 'إجمالي القطع',
                'low_stock_count' => 'المنتجات منخفضة المخزون',
                'out_of_stock_count' => 'المنتجات النافدة',
            ],
            'repairs' => [
                'received' => 'الأجهزة المستلمة',
                'in_progress' => 'قيد التنفيذ',
                'ready' => 'جاهزة للاستلام',
                'delivered' => 'تم تسليمها',
                'cancelled' => 'الطلبات الملغاة',
                'overdue' => 'الطلبات المتأخرة',
                'total_revenue' => 'إيرادات الصيانة',
                'total_cost' => 'تكلفة قطع الغيار',
                'total_profit' => 'ربح الصيانة',
                'profit_margin' => 'هامش الربح',
                'average_duration' => 'متوسط مدة الصيانة',
            ],
            'finance' => [
                'total_collected' => 'إجمالي المقبوضات',
                'collected_sales' => 'مقبوضات المبيعات',
                'collected_repairs' => 'مقبوضات الصيانة',
                'paid_to_suppliers' => 'المدفوع للموردين',
                'expenses' => 'المصروفات',
                'repair_part_purchases' => 'شراء قطع صيانة خارجية',
                'net_cash_flow' => 'صافي التدفق النقدي',
                'total_inflows' => 'إجمالي الوارد الفعلي',
                'total_outflows' => 'إجمالي الصادر الفعلي',
                'sales_refunds' => 'صافي المسترد للعملاء',
                'purchase_refunds' => 'صافي المسترد من الموردين',
                'exchange_net' => 'صافي فروقات الاستبدال',
                'other_net_flow' => 'صافي الحركات الأخرى',
                'reversal_count' => 'عدد حركات العكس',
                'transfer_volume' => 'حجم التحويلات الداخلية',
                'transfer_count' => 'عدد التحويلات المكتملة',
                'cancelled_transfer_count' => 'عدد التحويلات الملغاة',
                'customer_debts' => 'مستحقات العملاء',
                'supplier_debts' => 'مستحقات الموردين',
                'total_difference' => 'فروقات الإغلاق',
            ],
            'returns' => [
                'total_sales_returns' => 'مرتجعات المبيعات',
                'refunded_to_customers' => 'المبالغ المستردة للعملاء',
                'debt_reduced' => 'ديون العملاء المخفضة',
                'total_purchase_returns' => 'مرتجعات المشتريات',
                'refunded_from_suppliers' => 'المسترد من الموردين',
                'supplier_debt_reduced' => 'مستحقات الموردين المخفضة',
                'restocked_items' => 'القطع المعادة للمخزون',
                'damaged_items' => 'القطع التالفة',
                'needs_inspection' => 'قطع تحتاج فحصاً',
                'exchange_count' => 'عمليات الاستبدال',
                'total_price_difference' => 'إجمالي فروقات الأسعار',
            ],
            default => [],
        };
    }

    private function sectionDefinitions(string $type): array
    {
        return match ($type) {
            'sales' => [
                ['key' => 'top_products', 'title' => 'المنتجات الأكثر مبيعاً', 'columns' => [
                    'name' => 'المنتج', 'code' => 'الكود', 'total_quantity' => 'الكمية',
                    'total_revenue' => 'الإيراد', 'total_profit' => 'الربح',
                ]],
                ['key' => 'sales_by_category', 'title' => 'المبيعات حسب الفئة', 'columns' => [
                    'name' => 'الفئة', 'total' => 'الإجمالي',
                ]],
                ['key' => 'payments_by_method', 'title' => 'المقبوضات حسب طريقة الدفع', 'columns' => [
                    'payment_method' => 'طريقة الدفع', 'total' => 'الإجمالي',
                ]],
            ],
            'purchases' => [
                ['key' => 'purchases_by_supplier', 'title' => 'المشتريات حسب المورد', 'columns' => [
                    'name' => 'المورد', 'total' => 'الإجمالي',
                ]],
                ['key' => 'top_products', 'title' => 'المنتجات الأكثر شراءً', 'columns' => [
                    'name' => 'المنتج', 'code' => 'الكود', 'total_quantity' => 'الكمية', 'total_amount' => 'الإجمالي',
                ]],
                ['key' => 'overdue_invoices', 'title' => 'الفواتير المتأخرة', 'columns' => [
                    'invoice_number' => 'رقم الفاتورة', 'supplier.name' => 'المورد', 'due_date' => 'الاستحقاق', 'remaining_amount' => 'المتبقي',
                ]],
            ],
            'inventory' => [
                ['key' => 'inventory_data', 'title' => 'أرصدة المخزون', 'columns' => [
                    'product.name' => 'المنتج', 'product.code' => 'الكود', 'sales_qty' => 'مخزون المبيعات',
                    'maintenance_qty' => 'مخزون الصيانة', 'total_qty' => 'الإجمالي', 'total_value' => 'القيمة', 'stock_status' => 'الحالة',
                ]],
            ],
            'repairs' => [
                ['key' => 'device_types', 'title' => 'الأجهزة حسب النوع', 'columns' => ['label' => 'النوع', 'value' => 'العدد']],
                ['key' => 'faults', 'title' => 'الأعطال الأكثر تكراراً', 'columns' => ['label' => 'العطل', 'value' => 'العدد']],
                ['key' => 'top_parts', 'title' => 'قطع الغيار الأكثر استخداماً', 'columns' => [
                    'product.name' => 'القطعة', 'source' => 'المصدر', 'total_quantity' => 'الكمية',
                ]],
            ],
            'finance' => [
                ['key' => 'accounts', 'title' => 'أرصدة الحسابات', 'columns' => [
                    'name' => 'الحساب', 'type_label' => 'النوع', 'current_balance' => 'الرصيد',
                ]],
                ['key' => 'aging', 'title' => 'أعمار الديون', 'columns' => ['label' => 'الفترة', 'value' => 'القيمة']],
                ['key' => 'transfers', 'title' => 'التحويلات بين الحسابات', 'columns' => [
                    'transfer_number' => 'رقم التحويل', 'fromAccount.name' => 'من حساب', 'toAccount.name' => 'إلى حساب',
                    'amount' => 'المبلغ', 'transfer_date' => 'التاريخ', 'status' => 'الحالة',
                ]],
                ['key' => 'transactions', 'title' => 'الحركات المالية التفصيلية', 'columns' => [
                    'transaction_date' => 'التاريخ', 'account.name' => 'الحساب', 'type' => 'نوع الحركة',
                    'direction' => 'الاتجاه', 'description' => 'الوصف', 'amount' => 'المبلغ',
                    'balance_before' => 'الرصيد قبل', 'balance_after' => 'الرصيد بعد',
                ]],
                ['key' => 'closings', 'title' => 'آخر الإغلاقات اليومية', 'columns' => [
                    'closing_date' => 'التاريخ', 'account.name' => 'الحساب', 'expected_balance' => 'المتوقع',
                    'actual_balance' => 'الفعلي', 'difference' => 'الفرق',
                ]],
            ],
            'returns' => [
                ['key' => 'top_returned_products', 'title' => 'المنتجات الأكثر إرجاعاً', 'columns' => [
                    'name' => 'المنتج', 'code' => 'الكود', 'total_quantity' => 'الكمية', 'total_amount' => 'القيمة',
                ]],
                ['key' => 'exchanges', 'title' => 'عمليات الاستبدال', 'columns' => [
                    'exchange_number' => 'رقم العملية', 'return_value' => 'قيمة المرتجع',
                    'new_items_value' => 'قيمة البدائل', 'price_difference' => 'فرق السعر', 'exchanged_at' => 'التاريخ',
                ]],
            ],
            default => [],
        };
    }

    private function normaliseRows(mixed $source, array $columns): array
    {
        if ($source instanceof Collection) {
            $source = $source->all();
        } elseif ($source instanceof Arrayable) {
            $source = $source->toArray();
        }

        if (! is_array($source)) {
            return [];
        }

        if (! array_is_list($source)) {
            $source = collect($source)
                ->map(fn (mixed $value, string|int $key): array => ['label' => (string) $key, 'value' => $value])
                ->values()
                ->all();
        }

        return collect($source)->map(function (mixed $row) use ($columns): array {
            return collect(array_keys($columns))->map(function (string $key) use ($row): string {
                return $this->formatValue($key, data_get($row, $key));
            })->all();
        })->all();
    }

    private function formatValue(string $key, mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof \BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : (string) $value->value;
        }

        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_numeric($value)) {
            if (str_contains($key, 'margin')) {
                return number_format((float) $value, 2).'٪';
            }

            $moneyHints = ['amount', 'total', 'value', 'revenue', 'profit', 'cost', 'balance', 'paid', 'remaining', 'difference', 'sales', 'purchases', 'expenses', 'collected', 'debts', 'refunded'];
            if (collect($moneyHints)->contains(fn (string $hint): bool => str_contains($key, $hint))) {
                return number_format((float) $value, 2).' شيكل';
            }

            return number_format((float) $value, fmod((float) $value, 1.0) === 0.0 ? 0 : 2);
        }

        if ($key === 'payment_method') {
            return match ((string) $value) {
                'cash' => 'كاش',
                'bank_transfer' => 'تحويل بنكي',
                'banking_app' => 'تطبيق بنكي',
                default => (string) $value,
            };
        }

        if ($key === 'stock_status') {
            return match ((string) $value) {
                'available' => 'متوفر',
                'low' => 'منخفض',
                'out' => 'نافد',
                default => (string) $value,
            };
        }

        return (string) $value;
    }
}
