<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::table('spaces', function (Blueprint $table) {
            $table->date('available_from')->nullable()->after('capacity');
            $table->date('available_until')->nullable()->after('available_from');
            $table->string('main_image')->nullable()->after('active');
        });
    }

    public function down(): void {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn(['available_from', 'available_until', 'main_image']);
        });
    }
};