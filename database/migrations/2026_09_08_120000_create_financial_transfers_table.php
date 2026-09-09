<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transfers', function (Blueprint $table) {

            /*
             * يجب تحديد InnoDB بشكل صريح.
             *
             * قاعدة البيانات الحالية تستخدم MyISAM افتراضيًا،
             * ولذلك بدون هذا السطر قد يتم إنشاء financial_transfers
             * كـ MyISAM ثم تفشل الـ Foreign Keys مرة أخرى.
             */
            $table->engine = 'InnoDB';

            $table->id();

            $table->string('transfer_number')
                ->unique();

            $table->foreignId('from_account_id')
                ->constrained('financial_accounts')
                ->restrictOnDelete();

            $table->foreignId('to_account_id')
                ->constrained('financial_accounts')
                ->restrictOnDelete();

            $table->decimal('amount', 12, 2);

            $table->timestamp('transfer_date');

            $table->string('status')
                ->default('posted');

            $table->text('notes')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'transfer_date',
                'status',
            ]);

            $table->index([
                'from_account_id',
                'transfer_date',
            ]);

            $table->index([
                'to_account_id',
                'transfer_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transfers');
    }
};
