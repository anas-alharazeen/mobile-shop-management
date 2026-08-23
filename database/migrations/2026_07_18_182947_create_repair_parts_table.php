<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->boolean('is_committed')->default(false);
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts');
    }
};
