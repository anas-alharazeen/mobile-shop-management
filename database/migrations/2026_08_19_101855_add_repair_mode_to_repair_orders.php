<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn(
                'repair_orders',
                'repair_mode'
            )
        ) {
            return;
        }

        Schema::table(
            'repair_orders',
            function (
                Blueprint $table
            ): void {
                $table
                    ->string(
                        'repair_mode',
                        20
                    )
                    ->default(
                        'normal'
                    )
                    ->after(
                        'order_number'
                    )
                    ->index();
            }
        );
    }

    public function down(): void
    {
        if (
            ! Schema::hasColumn(
                'repair_orders',
                'repair_mode'
            )
        ) {
            return;
        }

        Schema::table(
            'repair_orders',
            function (
                Blueprint $table
            ): void {
                $table->dropColumn(
                    'repair_mode'
                );
            }
        );
    }
};
