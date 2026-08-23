<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            // The lock code is encrypted before storage. Even a short Arabic value
            // can exceed VARCHAR(255) after encryption and Base64 encoding.
            $table->text('lock_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep TEXT on rollback to avoid truncating already encrypted values.
        // Reducing it to VARCHAR could make rollback fail or corrupt data.
    }
};
