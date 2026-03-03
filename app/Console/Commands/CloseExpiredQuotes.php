<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quote;
use App\Enums\QuoteStatus;
use Carbon\Carbon;

class CloseExpiredQuotes extends Command
{
    protected $signature = 'quotes:expire';
    protected $description = 'Finaliza propostas que passaram da data de validade';

    public function handle()
    {
        $expiredCount = Quote::where('status', QuoteStatus::Respondido)
            ->where('valid_until', '<', Carbon::now())
            ->update(['status' => QuoteStatus::Rejeitado, 'rejection_reason' => 'Prazo de validade expirado.']);

        $this->info("{$expiredCount} propostas expiradas foram fechadas.");
    }
}