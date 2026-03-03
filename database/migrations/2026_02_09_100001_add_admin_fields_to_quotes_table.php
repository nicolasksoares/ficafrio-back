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
        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('admin_approved_at')->nullable()->after('rejection_reason');
            $table->foreignId('admin_approved_by')->nullable()->after('admin_approved_at')->constrained('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['admin_approved_by']);
            $table->dropColumn(['admin_approved_at', 'admin_approved_by']);
        });
    }
};
