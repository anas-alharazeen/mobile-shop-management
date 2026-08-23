<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_purchase_price', 12, 2);
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('allocated_expenses', 12, 2)->default(0);
            $table->decimal('landed_unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
