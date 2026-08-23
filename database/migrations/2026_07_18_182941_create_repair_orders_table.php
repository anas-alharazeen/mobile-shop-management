<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('device_type');
            $table->string('brand');
            $table->string('model');
            $table->string('color')->nullable();
            $table->text('problem_description');
            $table->text('device_condition')->nullable();
            $table->text('received_accessories')->nullable();
            $table->string('lock_code')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('status')->default('received');
            $table->string('sub_status')->default('waiting_inspection');
            $table->text('inspection_result')->nullable();
            $table->text('fault_cause')->nullable();
            $table->text('repair_action')->nullable();
            $table->string('customer_approval_status')->default('not_required');
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('inspection_fee', 12, 2)->default(0);
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->timestamp('received_at');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('warranty_days')->nullable();
            $table->timestamp('warranty_expires_at')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_orders');
    }
};
