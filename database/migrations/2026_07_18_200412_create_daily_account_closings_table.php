<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_account_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained()->cascadeOnDelete();
            $table->date('closing_date');
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('total_inflows', 12, 2);
            $table->decimal('total_outflows', 12, 2);
            $table->decimal('expected_balance', 12, 2);
            $table->decimal('actual_balance', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['financial_account_id', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_account_closings');
    }
};
