<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('spaces', function (Blueprint $table) {
            $table->index('city');
            $table->index('state');
            $table->index('type');
            $table->index(['temp_min', 'temp_max']);
        });

        Schema::table('storage_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index(['temp_min', 'temp_max']);
        });
    }

    public function down(): void {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropIndex(['city', 'state', 'type', 'temp_min', 'temp_max']);
        });

        Schema::table('storage_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'temp_min', 'temp_max']);
        });
    }
};