<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_code');
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('unit_selling_price', 12, 2);
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('allocated_invoice_discount', 12, 2)->default(0);
            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->decimal('line_cost', 12, 2);
            $table->decimal('line_profit', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
