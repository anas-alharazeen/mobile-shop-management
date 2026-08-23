<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('return_date');
            $table->string('status')->default('draft');
            $table->string('return_type')->default('partial');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_used_for_debt', 12, 2)->default(0);
            $table->decimal('amount_refunded', 12, 2)->default(0);
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_method')->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
