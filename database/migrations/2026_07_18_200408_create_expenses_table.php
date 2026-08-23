<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('beneficiary')->nullable();
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('posted');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
