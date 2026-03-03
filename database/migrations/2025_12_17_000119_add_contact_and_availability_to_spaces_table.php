<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->integer('available_pallet_positions')->after('capacity');
            $table->string('contact_name')->after('state');
            $table->string('contact_email')->after('state');
            $table->string('contact_phone')->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn(['available_pallet_positions', 'contact_email', 'contact_phone']);
        });
    }
};