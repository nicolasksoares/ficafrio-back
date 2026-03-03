<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('spaces', function (Blueprint $table) {
            // Altera de dateTime/timestamp para date (apenas dia, sem hora)
            $table->date('available_from')->change();
            $table->date('available_until')->change();
        });
    }
    
    public function down()
    {
        Schema::table('spaces', function (Blueprint $table) {
            // Reverte caso precise (assumindo que antes era dateTime)
            $table->dateTime('available_from')->change();
            $table->dateTime('available_until')->change();
        });
    }
};
