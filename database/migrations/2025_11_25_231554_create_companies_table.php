<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name');
            $table->string('legal_name');
            $table->string('cnpj')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone');
            $table->string('address_street')->nullable();
            $table->string('address_number')->nullable();
            $table->string('district')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code')->nullable();
            $table->string('type');
            $table->boolean('active')->default(true);
            
            $table->rememberToken(); // Adicione isso aqui!
            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('companies');
    }
};