<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales_exchanges', 'exchange_credit')) {
            Schema::table('sales_exchanges', function (Blueprint $table) {
                $table->decimal('exchange_credit', 12, 2)->default(0)->after('return_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_exchanges', 'exchange_credit')) {
            Schema::table('sales_exchanges', function (Blueprint $table) {
                $table->dropColumn('exchange_credit');
            });
        }
    }
};
