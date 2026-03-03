<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('storage_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('product_type');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->string('unit')->default('pallets');
            
            $table->integer('temp_min');
            $table->integer('temp_max');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pendente');

            // Campos novos para o Seeder
            $table->string('target_city')->nullable();
            $table->string('target_state')->nullable();
            $table->text('requester_message')->nullable();
            $table->decimal('proposed_price', 10, 2)->nullable();

            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('storage_requests');
    }
};