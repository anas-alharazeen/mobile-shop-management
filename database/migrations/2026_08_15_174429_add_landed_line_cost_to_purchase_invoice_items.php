<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table): void {
            /*
             * snapshot للقيمة الفعلية الكاملة للبند بعد:
             * - خصم البند
             * - حصة خصم الفاتورة
             * + حصة الشحن والمصاريف
             *
             * نحتاجه لأن landed_unit_cost مخزن بدقتين عشريتين،
             * وبالتالي ضربه في الكمية قد يفقد قرشاً أو يزيد قرشاً.
             */
            $table
                ->decimal('landed_line_cost', 14, 2)
                ->nullable()
                ->after('landed_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table): void {
            $table->dropColumn('landed_line_cost');
        });
    }
};
