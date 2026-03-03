<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * payment_url do Stripe Checkout pode exceder 255 caracteres.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN payment_url TYPE TEXT');
        } else {
            DB::statement('ALTER TABLE payments MODIFY payment_url TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN payment_url TYPE VARCHAR(255)');
        } else {
            DB::statement('ALTER TABLE payments MODIFY payment_url VARCHAR(255) NULL');
        }
    }
};
