<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * بعض فواتير المبيعات تكون لعميل نقدي/غير محفوظ،
     * لذلك sales_invoices.customer_id يمكن أن يكون NULL.
     *
     * مرتجع المبيعات يجب أن يتبع نفس المنطق، لأن مصدر
     * بيانات العميل الحقيقي يبقى الفاتورة الأصلية
     * (customer_name / customer_phone) حتى لو لم يوجد
     * سجل داخل جدول customers.
     */
    public function up(): void
    {
        /*
         * المفتاح القديم أُنشئ بـ cascadeOnDelete وكان العمود
         * NOT NULL. نسقط الـ FK أولاً قبل تغيير العمود.
         */
        Schema::table('sales_returns', function (Blueprint $table): void {
            $table->dropForeign([
                'customer_id',
            ]);
        });

        /*
         * السماح بـ NULL حتى نستطيع إنشاء مرتجع لفاتورة
         * عميل نقدي لم يتم حفظه في customers.
         */
        Schema::table('sales_returns', function (Blueprint $table): void {
            $table
                ->unsignedBigInteger('customer_id')
                ->nullable()
                ->change();
        });

        /*
         * لا نحذف المرتجع إذا حذف سجل العميل مستقبلاً.
         * السجل المالي والتاريخي يجب أن يبقى، لذلك نستخدم
         * nullOnDelete بدلاً من cascadeOnDelete.
         */
        Schema::table('sales_returns', function (Blueprint $table): void {
            $table
                ->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        /*
         * لا يمكن إعادة العمود إلى NOT NULL بأمان إذا أصبحت
         * هناك مرتجعات حقيقية لعملاء نقديين customer_id = NULL.
         */
        if (
            DB::table('sales_returns')
                ->whereNull('customer_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'لا يمكن التراجع عن هذا Migration لأن هناك مرتجعات مبيعات بدون customer_id.'
            );
        }

        Schema::table('sales_returns', function (Blueprint $table): void {
            $table->dropForeign([
                'customer_id',
            ]);
        });

        Schema::table('sales_returns', function (Blueprint $table): void {
            $table
                ->unsignedBigInteger('customer_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('sales_returns', function (Blueprint $table): void {
            $table
                ->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }
};
