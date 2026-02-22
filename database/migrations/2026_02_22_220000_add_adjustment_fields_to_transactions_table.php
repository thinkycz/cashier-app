<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('adjustment_type')->nullable()->after('discount');
            $table->decimal('adjustment_percent', 5, 2)->default(0)->after('adjustment_type');
            $table->decimal('adjustment_amount', 10, 2)->default(0)->after('adjustment_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['adjustment_type', 'adjustment_percent', 'adjustment_amount']);
        });
    }
};
