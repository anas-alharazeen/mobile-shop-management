<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::connection()->getDriverName() === 'mysql'
            && Schema::hasTable('financial_accounts')
        ) {
            DB::statement(
                'ALTER TABLE `financial_accounts` ENGINE=InnoDB'
            );
        }
    }

    public function down(): void
    {
        /*
         * لا نعيد الجدول إلى MyISAM عند rollback.
         *
         * InnoDB هو المحرك المطلوب لدعم:
         * - Foreign Keys
         * - DB Transactions
         * - Row Locks / lockForUpdate()
         *
         * وإعادته إلى MyISAM قد تكسر سلامة العمليات المالية.
         */
    }
};
