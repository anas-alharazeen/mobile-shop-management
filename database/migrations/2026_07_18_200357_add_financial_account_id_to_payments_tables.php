<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة إلى sales_payments
        if (!Schema::hasColumn('sales_payments', 'financial_account_id')) {
            Schema::table('sales_payments', function (Blueprint $table) {
                $table->foreignId('financial_account_id')->nullable()->after('amount')->constrained()->nullOnDelete();
            });
        }

        // إضافة إلى purchase_payments
        if (!Schema::hasColumn('purchase_payments', 'financial_account_id')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->foreignId('financial_account_id')->nullable()->after('amount')->constrained()->nullOnDelete();
            });
        }

        // إضافة إلى repair_payments
        if (!Schema::hasColumn('repair_payments', 'financial_account_id')) {
            Schema::table('repair_payments', function (Blueprint $table) {
                $table->foreignId('financial_account_id')->nullable()->after('amount')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['sales_payments', 'purchase_payments', 'repair_payments'] as $tableName) {
            if (Schema::hasColumn($tableName, 'financial_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('financial_account_id');
                });
            }
        }
    }
};
