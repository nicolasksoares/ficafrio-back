<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('zip_code');
            $table->string('address');
            $table->string('number');
            $table->string('district');
            $table->string('city');
            $table->string('state', 2);
            $table->integer('temp_min');
            $table->integer('temp_max');
            $table->integer('capacity');
            $table->string('capacity_unit')->default('pallets');
            $table->string('type');
            $table->boolean('has_anvisa')->default(false);
            $table->boolean('has_security')->default(false);
            $table->boolean('has_generator')->default(false);
            $table->boolean('has_dock')->default(false);
            $table->string('operating_hours')->nullable();
            $table->boolean('allows_extended_hours')->default(false);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};