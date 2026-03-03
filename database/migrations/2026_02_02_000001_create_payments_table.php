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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->comment('Quem paga');
            $table->foreignId('space_owner_id')->constrained('companies')->onDelete('cascade')->comment('Quem recebe');
            
            $table->decimal('amount', 10, 2)->comment('Valor total da Quote');
            $table->decimal('platform_fee', 10, 2)->default(0)->comment('Taxa da plataforma (10%)');
            $table->decimal('net_amount', 10, 2)->default(0)->comment('Valor líquido para dono do espaço');
            
            $table->string('payment_method')->nullable()->comment('pix, credit_card, boleto');
            $table->string('status')->default('pending')->comment('pending, processing, paid, failed, refunded, cancelled');
            
            $table->string('gateway')->nullable()->comment('Nome do gateway usado');
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable()->comment('Resposta completa do gateway');
            
            $table->string('payment_url')->nullable()->comment('URL para pagamento (PIX/Boleto)');
            $table->text('payment_code')->nullable()->comment('Código PIX ou linha digitável');
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->json('metadata')->nullable()->comment('Dados extras');
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('quote_id');
            $table->index('company_id');
            $table->index('space_owner_id');
            $table->index('status');
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

