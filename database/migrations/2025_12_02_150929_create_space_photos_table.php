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
        Schema::create('space_photos', function (Blueprint $table) {
            $table->id();

            // Vínculo com a Câmara
            $table->foreignId('space_id')->constrained()->onDelete('cascade');

            // Caminho do arquivo (ex: spaces/1/foto01.jpg)
            $table->string('path');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_photos');
    }
};
