<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_quantity')->default(0);
            $table->integer('actual_quantity')->nullable();
            $table->integer('difference')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('difference_value', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};
