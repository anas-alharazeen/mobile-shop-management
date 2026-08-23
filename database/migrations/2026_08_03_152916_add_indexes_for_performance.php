<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========== فواتير البيع ==========
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index('status', 'si_status_idx');
            $table->index('payment_status', 'si_payment_status_idx');
            $table->index('created_at', 'si_created_at_idx');
            $table->index('sale_date', 'si_sale_date_idx');
            $table->index(['customer_id', 'status'], 'si_customer_status_idx');
            $table->index(['status', 'created_at'], 'si_status_created_idx');
        });

        // ========== فواتير الشراء ==========
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->index('status', 'pi_status_idx');
            $table->index('payment_status', 'pi_payment_status_idx');
            $table->index('created_at', 'pi_created_at_idx');
            $table->index('purchase_date', 'pi_purchase_date_idx');
            $table->index(['supplier_id', 'status'], 'pi_supplier_status_idx');
        });

        // ========== طلبات الصيانة ==========
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->index('status', 'ro_status_idx');
            $table->index('payment_status', 'ro_payment_status_idx');
            $table->index('received_at', 'ro_received_at_idx');
            $table->index('expected_delivery_date', 'ro_expected_delivery_idx');
            $table->index('customer_id', 'ro_customer_id_idx');
            $table->index(['status', 'expected_delivery_date'], 'ro_status_expected_idx');
        });

        // ========== المنتجات ==========
        Schema::table('products', function (Blueprint $table) {
            $table->index('code', 'p_code_idx');
            $table->index('barcode', 'p_barcode_idx');
            $table->index('is_active', 'p_is_active_idx');
            $table->index('category_id', 'p_category_id_idx');
            $table->index(['category_id', 'is_active'], 'p_category_active_idx');
        });

        // ========== أرصدة المخزون ==========
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->index(['product_id', 'warehouse_id'], 'ps_product_warehouse_idx');
            $table->index('quantity', 'ps_quantity_idx');
        });

        // ========== الموردون ==========
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('phone', 's_phone_idx');
            $table->index('is_active', 's_is_active_idx');
            $table->index('code', 's_code_idx');
        });

        // ========== العملاء ==========
        Schema::table('customers', function (Blueprint $table) {
            $table->index('phone', 'c_phone_idx');
            $table->index('is_active', 'c_is_active_idx');
            $table->index('code', 'c_code_idx');
        });

        // ========== حركات المخزون ==========
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('type', 'sm_type_idx');
            $table->index('created_at', 'sm_created_at_idx');
            $table->index(['product_id', 'warehouse_id'], 'sm_product_warehouse_idx');
            $table->index(['reference_type', 'reference_id'], 'sm_reference_idx');
        });

        // ========== المصروفات ==========
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('status', 'e_status_idx');
            $table->index('expense_date', 'e_expense_date_idx');
            $table->index('expense_category_id', 'e_category_id_idx');
            $table->index('financial_account_id', 'e_account_id_idx');
        });

        // ========== مرتجعات المبيعات ==========
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->index('status', 'sr_status_idx');
            $table->index('return_date', 'sr_return_date_idx');
            $table->index(['sales_invoice_id', 'status'], 'sr_invoice_status_idx');
        });

        // ========== مرتجعات المشتريات ==========
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->index('status', 'pr_status_idx');
            $table->index('return_date', 'pr_return_date_idx');
            $table->index(['purchase_invoice_id', 'status'], 'pr_invoice_status_idx');
        });

        // ========== دفعات المبيعات ==========
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->index('payment_method', 'sp_payment_method_idx');
            $table->index('paid_at', 'sp_paid_at_idx');
            $table->index(['sales_invoice_id', 'paid_at'], 'sp_invoice_paid_idx');
        });

        // ========== دفعات المشتريات ==========
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->index('payment_method', 'pp_payment_method_idx');
            $table->index('paid_at', 'pp_paid_at_idx');
            $table->index(['purchase_invoice_id', 'paid_at'], 'pp_invoice_paid_idx');
        });

        // ========== دفعات الصيانة ==========
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->index('payment_method', 'rp_payment_method_idx');
            $table->index('paid_at', 'rp_paid_at_idx');
            $table->index(['repair_order_id', 'paid_at'], 'rp_order_paid_idx');
        });

        // ========== الحركات المالية ==========
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->index('type', 'ft_type_idx');
            $table->index('direction', 'ft_direction_idx');
            $table->index('transaction_date', 'ft_transaction_date_idx');
            $table->index(['financial_account_id', 'transaction_date'], 'ft_account_date_idx');
            $table->index(['reference_type', 'reference_id'], 'ft_reference_idx');
        });

        // ========== عناصر فواتير البيع ==========
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->index(['product_id', 'sales_invoice_id'], 'sii_product_invoice_idx');
            $table->index('warehouse_id', 'sii_warehouse_id_idx');
        });

        // ========== عناصر فواتير الشراء ==========
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->index(['product_id', 'purchase_invoice_id'], 'pii_product_invoice_idx');
            $table->index('warehouse_id', 'pii_warehouse_id_idx');
        });

        // ========== عناصر مرتجعات المبيعات ==========
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->index('product_id', 'sri_product_id_idx');
            $table->index(['sales_return_id', 'product_id'], 'sri_return_product_idx');
        });

        // ========== عناصر مرتجعات المشتريات ==========
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->index('product_id', 'pri_product_id_idx');
            $table->index('warehouse_id', 'pri_warehouse_id_idx');
            $table->index(['purchase_return_id', 'product_id'], 'pri_return_product_idx');
        });

        // ========== قطع غيار الصيانة ==========
        Schema::table('repair_parts', function (Blueprint $table) {
            $table->index('product_id', 'rpart_product_id_idx');
            $table->index('warehouse_id', 'rpart_warehouse_id_idx');
            $table->index('is_committed', 'rpart_is_committed_idx');
            $table->index(['repair_order_id', 'product_id'], 'rpart_order_product_idx');
        });

        // ========== جلسات الجرد ==========
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->index('status', 'ic_status_idx');
            $table->index('count_date', 'ic_count_date_idx');
            $table->index(['warehouse_id', 'status'], 'ic_warehouse_status_idx');
        });

        // ========== عناصر الجرد ==========
        Schema::table('inventory_count_items', function (Blueprint $table) {
            $table->index('product_id', 'ici_product_id_idx');
            $table->index(['inventory_count_id', 'product_id'], 'ici_count_product_idx');
        });

        // ========== الإعدادات ==========
        Schema::table('settings', function (Blueprint $table) {
            $table->index('key', 'set_key_idx');
            $table->index('group', 'set_group_idx');
        });

        // ========== الإغلاق اليومي ==========
        Schema::table('daily_account_closings', function (Blueprint $table) {
            $table->index('closing_date', 'dac_closing_date_idx');
            $table->index(['financial_account_id', 'closing_date'], 'dac_account_date_idx');
        });
    }

    public function down(): void
    {
        // ========== فواتير البيع ==========
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('si_status_idx');
            $table->dropIndex('si_payment_status_idx');
            $table->dropIndex('si_created_at_idx');
            $table->dropIndex('si_sale_date_idx');
            $table->dropIndex('si_customer_status_idx');
            $table->dropIndex('si_status_created_idx');
        });

        // ========== فواتير الشراء ==========
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('pi_status_idx');
            $table->dropIndex('pi_payment_status_idx');
            $table->dropIndex('pi_created_at_idx');
            $table->dropIndex('pi_purchase_date_idx');
            $table->dropIndex('pi_supplier_status_idx');
        });

        // ========== طلبات الصيانة ==========
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropIndex('ro_status_idx');
            $table->dropIndex('ro_payment_status_idx');
            $table->dropIndex('ro_received_at_idx');
            $table->dropIndex('ro_expected_delivery_idx');
            $table->dropIndex('ro_customer_id_idx');
            $table->dropIndex('ro_status_expected_idx');
        });

        // ========== المنتجات ==========
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('p_code_idx');
            $table->dropIndex('p_barcode_idx');
            $table->dropIndex('p_is_active_idx');
            $table->dropIndex('p_category_id_idx');
            $table->dropIndex('p_category_active_idx');
        });

        // ========== أرصدة المخزون ==========
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndex('ps_product_warehouse_idx');
            $table->dropIndex('ps_quantity_idx');
        });

        // ========== الموردون ==========
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('s_phone_idx');
            $table->dropIndex('s_is_active_idx');
            $table->dropIndex('s_code_idx');
        });

        // ========== العملاء ==========
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('c_phone_idx');
            $table->dropIndex('c_is_active_idx');
            $table->dropIndex('c_code_idx');
        });

        // ========== حركات المخزون ==========
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('sm_type_idx');
            $table->dropIndex('sm_created_at_idx');
            $table->dropIndex('sm_product_warehouse_idx');
            $table->dropIndex('sm_reference_idx');
        });

        // ========== المصروفات ==========
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('e_status_idx');
            $table->dropIndex('e_expense_date_idx');
            $table->dropIndex('e_category_id_idx');
            $table->dropIndex('e_account_id_idx');
        });

        // ========== مرتجعات المبيعات ==========
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropIndex('sr_status_idx');
            $table->dropIndex('sr_return_date_idx');
            $table->dropIndex('sr_invoice_status_idx');
        });

        // ========== مرتجعات المشتريات ==========
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropIndex('pr_status_idx');
            $table->dropIndex('pr_return_date_idx');
            $table->dropIndex('pr_invoice_status_idx');
        });

        // ========== دفعات المبيعات ==========
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->dropIndex('sp_payment_method_idx');
            $table->dropIndex('sp_paid_at_idx');
            $table->dropIndex('sp_invoice_paid_idx');
        });

        // ========== دفعات المشتريات ==========
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropIndex('pp_payment_method_idx');
            $table->dropIndex('pp_paid_at_idx');
            $table->dropIndex('pp_invoice_paid_idx');
        });

        // ========== دفعات الصيانة ==========
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->dropIndex('rp_payment_method_idx');
            $table->dropIndex('rp_paid_at_idx');
            $table->dropIndex('rp_order_paid_idx');
        });

        // ========== الحركات المالية ==========
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex('ft_type_idx');
            $table->dropIndex('ft_direction_idx');
            $table->dropIndex('ft_transaction_date_idx');
            $table->dropIndex('ft_account_date_idx');
            $table->dropIndex('ft_reference_idx');
        });

        // ========== عناصر فواتير البيع ==========
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropIndex('sii_product_invoice_idx');
            $table->dropIndex('sii_warehouse_id_idx');
        });

        // ========== عناصر فواتير الشراء ==========
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropIndex('pii_product_invoice_idx');
            $table->dropIndex('pii_warehouse_id_idx');
        });

        // ========== عناصر مرتجعات المبيعات ==========
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->dropIndex('sri_product_id_idx');
            $table->dropIndex('sri_return_product_idx');
        });

        // ========== عناصر مرتجعات المشتريات ==========
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropIndex('pri_product_id_idx');
            $table->dropIndex('pri_warehouse_id_idx');
            $table->dropIndex('pri_return_product_idx');
        });

        // ========== قطع غيار الصيانة ==========
        Schema::table('repair_parts', function (Blueprint $table) {
            $table->dropIndex('rpart_product_id_idx');
            $table->dropIndex('rpart_warehouse_id_idx');
            $table->dropIndex('rpart_is_committed_idx');
            $table->dropIndex('rpart_order_product_idx');
        });

        // ========== جلسات الجرد ==========
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->dropIndex('ic_status_idx');
            $table->dropIndex('ic_count_date_idx');
            $table->dropIndex('ic_warehouse_status_idx');
        });

        // ========== عناصر الجرد ==========
        Schema::table('inventory_count_items', function (Blueprint $table) {
            $table->dropIndex('ici_product_id_idx');
            $table->dropIndex('ici_count_product_idx');
        });

        // ========== الإعدادات ==========
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('set_key_idx');
            $table->dropIndex('set_group_idx');
        });

        // ========== الإغلاق اليومي ==========
        Schema::table('daily_account_closings', function (Blueprint $table) {
            $table->dropIndex('dac_closing_date_idx');
            $table->dropIndex('dac_account_date_idx');
        });
    }
};
