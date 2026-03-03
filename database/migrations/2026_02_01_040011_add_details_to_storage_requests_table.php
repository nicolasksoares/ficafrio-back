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
        Schema::table('storage_requests', function (Blueprint $table) {
            // Adicionando os campos que faltam
            $table->string('title')->after('company_id');
            $table->string('category')->nullable()->after('title');
            
            // Dados de contato (se ainda não existirem)
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
        });
    }
    
    public function down(): void
    {
        Schema::table('storage_requests', function (Blueprint $table) {
            $table->dropColumn(['title', 'category', 'contact_name', 'contact_phone', 'contact_email']);
        });
    }
};
