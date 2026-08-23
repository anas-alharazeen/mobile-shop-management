<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_exchanges', function (Blueprint $table) {
            $table->id();
            $table->string('exchange_number')->unique();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('new_sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('return_value', 12, 2);
            $table->decimal('new_items_value', 12, 2);
            $table->decimal('price_difference', 12, 2);
            $table->string('settlement_type'); // customer_pays, customer_gets_refund, no_difference
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('exchanged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_exchanges');
    }
};
