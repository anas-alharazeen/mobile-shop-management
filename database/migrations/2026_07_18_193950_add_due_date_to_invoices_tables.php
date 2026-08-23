<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة due_date إلى sales_invoices إذا لم يكن موجوداً
        if (!Schema::hasColumn('sales_invoices', 'due_date')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('sale_date');
            });
        }

        // إضافة due_date إلى purchase_invoices إذا لم يكن موجوداً
        if (!Schema::hasColumn('purchase_invoices', 'due_date')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('purchase_date');
            });
        }

        // إضافة due_date إلى repair_orders إذا لم يكن موجوداً
        if (!Schema::hasColumn('repair_orders', 'due_date')) {
            Schema::table('repair_orders', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('received_at');
            });
        }
    }

    public function down(): void
    {
        // التراجع
    }
};
