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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('storage_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('space_id')->constrained()->onDelete('cascade');

            $table->decimal('price', 10, 2)->nullable();

            $table->date('valid_until')->nullable();

            $table->string('status')->default('solicitado');

            $table->text('rejection_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['storage_request_id', 'space_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
