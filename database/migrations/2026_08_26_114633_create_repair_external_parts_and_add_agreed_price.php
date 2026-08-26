<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('repair_orders', 'agreed_price')) {
            Schema::table('repair_orders', function (Blueprint $table): void {
                $table
                    ->decimal('agreed_price', 12, 2)
                    ->nullable()
                    ->after('estimated_cost');
            });
        }

        if (! Schema::hasTable('repair_external_parts')) {
            Schema::create('repair_external_parts', function (Blueprint $table): void {
                $table->id();
                $table
                    ->foreignId('repair_order_id')
                    ->constrained('repair_orders')
                    ->restrictOnDelete();
                $table
                    ->foreignId('supplier_id')
                    ->nullable()
                    ->constrained('suppliers')
                    ->nullOnDelete();
                $table
                    ->foreignId('financial_account_id')
                    ->nullable()
                    ->constrained('financial_accounts')
                    ->nullOnDelete();
                $table
                    ->foreignId('financial_transaction_id')
                    ->nullable()
                    ->constrained('financial_transactions')
                    ->nullOnDelete();

                $table->string('part_name');
                $table->unsignedInteger('quantity')->default(1);
                $table->string('status', 20)->default('draft')->index();

                $table->string('purchase_from')->nullable();
                $table->string('supplier_phone')->nullable();
                $table->string('purchase_reference')->nullable();

                $table->decimal('unit_purchase_price', 12, 2)->nullable();
                $table->decimal('total_purchase_cost', 12, 2)->default(0);
                $table->decimal('customer_unit_price', 12, 2)->nullable();
                $table->decimal('total_customer_price', 12, 2)->default(0);

                $table->timestamp('purchased_at')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['repair_order_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_external_parts');

        if (Schema::hasColumn('repair_orders', 'agreed_price')) {
            Schema::table('repair_orders', function (Blueprint $table): void {
                $table->dropColumn('agreed_price');
            });
        }
    }
};
