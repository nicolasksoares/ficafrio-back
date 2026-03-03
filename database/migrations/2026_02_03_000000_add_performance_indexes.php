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
        // Índices para tabela payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'payments_company_status_idx');
            $table->index(['status', 'created_at'], 'payments_status_created_idx');
            $table->index(['space_owner_id', 'status'], 'payments_owner_status_idx');
            $table->index('quote_id', 'payments_quote_idx');
        });

        // Índices para tabela quotes
        Schema::table('quotes', function (Blueprint $table) {
            $table->index('status', 'quotes_status_idx');
            $table->index(['storage_request_id', 'status'], 'quotes_request_status_idx');
            $table->index(['space_id', 'status'], 'quotes_space_status_idx');
            $table->index('valid_until', 'quotes_valid_until_idx');
        });

        // Índices para tabela spaces
        Schema::table('spaces', function (Blueprint $table) {
            $table->index(['city', 'state'], 'spaces_city_state_idx');
            $table->index(['status', 'active'], 'spaces_status_active_idx');
            $table->index(['company_id', 'status'], 'spaces_company_status_idx');
        });

        // Índices para tabela storage_requests
        Schema::table('storage_requests', function (Blueprint $table) {
            $table->index(['target_city', 'target_state'], 'storage_requests_location_idx');
            $table->index(['company_id', 'status'], 'storage_requests_company_status_idx');
            $table->index('status', 'storage_requests_status_idx');
        });

        // Índices para tabela companies
        Schema::table('companies', function (Blueprint $table) {
            $table->index('type', 'companies_type_idx');
            $table->index(['active', 'type'], 'companies_active_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_company_status_idx');
            $table->dropIndex('payments_status_created_idx');
            $table->dropIndex('payments_owner_status_idx');
            $table->dropIndex('payments_quote_idx');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_status_idx');
            $table->dropIndex('quotes_request_status_idx');
            $table->dropIndex('quotes_space_status_idx');
            $table->dropIndex('quotes_valid_until_idx');
        });

        Schema::table('spaces', function (Blueprint $table) {
            $table->dropIndex('spaces_city_state_idx');
            $table->dropIndex('spaces_status_active_idx');
            $table->dropIndex('spaces_company_status_idx');
        });

        Schema::table('storage_requests', function (Blueprint $table) {
            $table->dropIndex('storage_requests_location_idx');
            $table->dropIndex('storage_requests_company_status_idx');
            $table->dropIndex('storage_requests_status_idx');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_type_idx');
            $table->dropIndex('companies_active_type_idx');
        });
    }
};

