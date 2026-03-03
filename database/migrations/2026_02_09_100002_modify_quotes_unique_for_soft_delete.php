<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove o unique constraint para permitir nova submissão após rejeição (soft delete).
     * A validação de unicidade é feita na aplicação (apenas Quotes não-deletadas).
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['storage_request_id', 'space_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unique(['storage_request_id', 'space_id']);
        });
    }
};
