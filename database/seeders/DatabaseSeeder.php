<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Enums\SpaceStatus;
use App\Enums\SpaceType;
use App\Enums\QuoteStatus;
use App\Enums\ProductType;
use App\Enums\RequestStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\Company;
use App\Models\Space;
use App\Models\SpacePhoto;
use App\Models\StorageRequest;
use App\Models\Quote;
use App\Models\QuoteHistory;
use App\Models\Payment;
use App\Notifications\QuoteStatusChanged;
use App\Notifications\PaymentCreated;
use App\Notifications\PaymentProcessing;
use App\Notifications\PaymentConfirmed;
use App\Notifications\PaymentFailed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seed do banco de dados...');

        // 1. CRIAR ADMIN
        $admin = Company::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ficafrio.com')],
            [
                'trade_name' => 'Fica Frio Admin',
                'legal_name' => 'Fica Frio Gestão Logística Ltda',
                'cnpj' => '00.000.000/0001-00',
                'password' => 'password',
                'phone' => '11999999999',
                'city' => 'Belo Horizonte',
                'state' => 'MG',
                'type' => UserType::Admin,
                'active' => true,
                'address_street' => 'Av. do Contorno',
                'address_number' => '1000',
                'district' => 'Savassi',
                'zip_code' => '30110-000',
            ]
        );
        $this->command->info('✅ Admin criado');

        // 2. LISTA DE USUÁRIOS
        $usersData = [
            ['name' => 'Nicolas', 'city' => 'Belo Horizonte', 'state' => 'MG', 'dd' => '31'],
            ['name' => 'Maria', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'dd' => '21'],
            ['name' => 'Joao', 'city' => 'Sao Paulo', 'state' => 'SP', 'dd' => '11'],
            ['name' => 'Carlos', 'city' => 'Sao Paulo', 'state' => 'SP', 'dd' => '11'],
            ['name' => 'Ana', 'city' => 'Belo Horizonte', 'state' => 'MG', 'dd' => '31'],
            ['name' => 'Pedro', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'dd' => '21'],
            ['name' => 'Fernanda', 'city' => 'Sao Paulo', 'state' => 'SP', 'dd' => '11'],
            ['name' => 'Lucas', 'city' => 'Belo Horizonte', 'state' => 'MG', 'dd' => '31'],
            ['name' => 'Juliana', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'dd' => '21'],
            ['name' => 'Roberto', 'city' => 'Sao Paulo', 'state' => 'SP', 'dd' => '11'],
        ];

        $createdCompanies = [];

        foreach ($usersData as $index => $data) {
            $email = strtolower($data['name']) . '@teste.com';
            
            $company = Company::firstOrCreate(
                ['email' => $email],
                [
                    'trade_name' => $data['name'] . ' Logística',
                    'legal_name' => $data['name'] . ' Transportes Ltda',
                    'cnpj' => '10.000.000/0001-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'password' => 'password',
                    'phone' => $data['dd'] . '988887777',
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'type' => UserType::Cliente,
                    'active' => true,
                    'address_street' => 'Rua Principal',
                    'address_number' => rand(100, 999),
                    'district' => 'Centro Logístico',
                    'zip_code' => '30000-000',
                ]
            );

            $createdCompanies[] = $company;

            // Criar espaços com diferentes status
            $this->createSpace($company, SpaceStatus::Aprovado, true, SpaceType::Congelado, -20, -10, "Câmara 01 (Congelados)");
            $this->createSpace($company, SpaceStatus::Aprovado, true, SpaceType::Resfriado, 2, 8, "Câmara 02 (Resfriados)");
            $this->createSpace($company, SpaceStatus::Rejeitado, false, SpaceType::Resfriado, 10, 15, "Galpão Antigo (Reprovado)");
            $this->createSpace($company, SpaceStatus::EmAnalise, false, SpaceType::Congelado, -18, -5, "Nova Expansão (Análise)");
        }
        $this->command->info('✅ ' . count($createdCompanies) . ' companies e espaços criados');

        // 3. GERAR DEMANDAS, QUOTES E PAYMENTS
        $this->command->info('🔄 Gerando demandas, cotações e pagamentos...');

        $quotesAceitas = [];
        $quotesRespondidas = [];
        $quotesEmAnaliseAdmin = [];
        $quotesSolicitadas = [];
        $paymentsCriados = 0;

        foreach ($createdCompanies as $buyer) {
            for ($i = 0; $i < 3; $i++) { // Aumentar para 3 por company
                $productType = match($i % 3) {
                    0 => ProductType::CarnesProteinas,
                    1 => ProductType::Laticinios,
                    default => ProductType::FrutasVegetais,
                };

                // Variar datas para ter histórico
                $daysAgo = rand(0, 30);
                $startDate = Carbon::now()->addDays(rand(5, 15));
                $endDate = Carbon::now()->addDays(rand(30, 60));

                $request = StorageRequest::create([
                    'company_id' => $buyer->id,
                    'title' => 'Demanda ' . ($i+1) . ' - ' . $productType->label(),
                    'product_type' => $productType,
                    'description' => 'Preciso armazenar lote para distribuição regional.',
                    'quantity' => rand(50, 300),
                    'unit' => 'pallets',
                    'temp_min' => $i % 2 == 0 ? -18 : 2,
                    'temp_max' => $i % 2 == 0 ? -10 : 8,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'target_city' => $buyer->city,
                    'target_state' => $buyer->state,
                    'requester_message' => 'Temos urgência no início da operação.',
                    'proposed_price' => rand(2000, 8000),
                    'contact_name' => $buyer->trade_name,
                    'contact_phone' => $buyer->phone,
                    'contact_email' => $buyer->email,
                    'status' => RequestStatus::Pendente,
                    'created_at' => Carbon::now()->subDays($daysAgo),
                    'updated_at' => Carbon::now()->subDays($daysAgo),
                ]);

                // Tenta achar um espaço compatível de OUTRA empresa
                $targetSpace = Space::where('company_id', '!=', $buyer->id)
                    ->where('status', SpaceStatus::Aprovado)
                    ->where('active', true)
                    ->inRandomOrder()
                    ->first();

                if ($targetSpace) {
                    // Distribuir status de forma mais controlada (inclui em_analise_admin)
                    $statusDistribution = [
                        QuoteStatus::Solicitado->value => 25,       // 25%
                        QuoteStatus::EmAnaliseAdmin->value => 20,   // 20% - aguardando aprovação admin
                        QuoteStatus::Respondido->value => 20,       // 20% - admin já aprovou
                        QuoteStatus::Aceito->value => 25,           // 25%
                        QuoteStatus::Rejeitado->value => 10,        // 10%
                    ];
                    
                    $statusValue = $this->weightedRandom($statusDistribution);
                    $status = QuoteStatus::from($statusValue);
                    $price = in_array($status, [QuoteStatus::EmAnaliseAdmin, QuoteStatus::Respondido, QuoteStatus::Aceito]) ? rand(3000, 9000) : null;
                    $validUntil = ($status !== QuoteStatus::Solicitado)
                        ? Carbon::now()->addDays(rand(7, 15))
                        : null;

                    $quoteData = [
                        'storage_request_id' => $request->id,
                        'space_id' => $targetSpace->id,
                        'status' => $status,
                        'price' => $price,
                        'valid_until' => $validUntil,
                        'rejection_reason' => ($status === QuoteStatus::Rejeitado) ? 'Sem disponibilidade no momento.' : null,
                        'created_at' => Carbon::now()->subDays($daysAgo),
                        'updated_at' => Carbon::now()->subDays($daysAgo),
                    ];

                    // Quotes aprovadas pelo admin devem ter admin_approved_at e admin_approved_by
                    if ($status === QuoteStatus::Respondido) {
                        $quoteData['admin_approved_at'] = Carbon::now()->subDays($daysAgo);
                        $quoteData['admin_approved_by'] = $admin->id;
                    }

                    $quote = Quote::create($quoteData);

                    // Criar histórico da quote (parceiro=em_analise_admin, admin=respondido, buyer=solicitado/aceito)
                    $historyActor = match($status) {
                        QuoteStatus::EmAnaliseAdmin => $targetSpace->company,
                        QuoteStatus::Respondido => $admin,
                        default => $buyer,
                    };
                    $this->createQuoteHistory($quote, $historyActor, $status, $daysAgo);

                    // Se Quote foi aceita, criar payment e deduzir inventário
                    if ($status === QuoteStatus::Aceito && $price) {
                        // Deduzir inventário do espaço
                        $targetSpace->decrement('available_pallet_positions', $request->quantity);
                        
                        // Criar payment
                        $payment = $this->createPaymentForQuote($quote, $buyer, $targetSpace->company, $daysAgo);
                        $quotesAceitas[] = $quote;
                        $paymentsCriados++;
                        
                        // Atualizar quote com payment_id
                        $quote->update(['payment_id' => $payment->id]);
                    } elseif ($status === QuoteStatus::Respondido) {
                        $quotesRespondidas[] = $quote;
                    } elseif ($status === QuoteStatus::EmAnaliseAdmin) {
                        $quotesEmAnaliseAdmin[] = $quote;
                    } elseif ($status === QuoteStatus::Solicitado) {
                        $quotesSolicitadas[] = $quote;
                    }

                    // Criar notificações
                    $this->createNotifications($quote, $status, $buyer, $targetSpace->company);
                }
            }
        }

        $this->command->info('✅ ' . count($quotesAceitas) . ' quotes aceitas com payments');
        $this->command->info('✅ ' . count($quotesRespondidas) . ' quotes respondidas (aprovadas pelo admin)');
        $this->command->info('✅ ' . count($quotesEmAnaliseAdmin) . ' quotes em análise pelo admin');
        $this->command->info('✅ ' . count($quotesSolicitadas) . ' quotes solicitadas');
        $this->command->info('✅ ' . $paymentsCriados . ' payments criados');
        $this->command->info('🎉 Seed concluído com sucesso!');
    }

    private function createSpace($company, $status, $active, $type, $minT, $maxT, $nameSuffix)
    {
        $space = Space::create([
            'company_id' => $company->id,
            'name' => "{$company->trade_name} - {$nameSuffix}",
            'description' => "Espaço localizado em {$company->city}.",
            'zip_code' => $company->zip_code ?? '30000-000',
            'address' => $company->address_street ?? 'Rua Principal',
            'number' => (string)rand(1, 999),
            'district' => $company->district ?? 'Centro',
            'city' => $company->city,
            'state' => $company->state,
            'temp_min' => $minT,
            'temp_max' => $maxT,
            'capacity' => 1000,
            'available_pallet_positions' => $status === SpaceStatus::Aprovado ? rand(200, 800) : 0,
            'type' => $type,
            'status' => $status,
            'active' => $active,
            'available_from' => Carbon::now(),
            'available_until' => Carbon::now()->addYear(),
            'operating_hours' => '08:00 - 18:00',
            'has_anvisa' => true,
            'has_security' => true,
            'has_generator' => ($type === SpaceType::Congelado),
            'has_dock' => true,
            'contact_name' => explode(' ', $company->trade_name)[0] . " Gerente",
            'contact_phone' => $company->phone,
            'contact_email' => $company->email,
        ]);

        $this->attachSeederPhotoToSpace($space);
    }

    /**
     * Anexa uma foto de database/seeders/assets/ ao espaço.
     */
    private function attachSeederPhotoToSpace(Space $space): void
    {
        $assetsDir = database_path('seeders/assets');
        if (!is_dir($assetsDir)) {
            return;
        }

        $files = glob($assetsDir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
        if (empty($files)) {
            return;
        }

        $sourcePath = $files[array_rand($files)];
        $filename = basename($sourcePath);
        $destPath = "spaces/{$space->id}/{$filename}";

        try {
            Storage::disk('public')->put($destPath, file_get_contents($sourcePath));

            $space->photos()->create(['path' => $destPath]);
            $space->update(['main_image' => $destPath]);
        } catch (\Throwable $e) {
            // Silently skip se erro (ex: storage link não criado)
        }
    }

    private function createQuoteHistory(Quote $quote, Company $company, QuoteStatus $status, int $daysAgo)
    {
        $actionData = match($status) {
            QuoteStatus::Solicitado => [
                'action' => 'solicitado',
                'description' => "{$company->trade_name} solicitou cotação para {$quote->storageRequest->quantity} paletes.",
            ],
            QuoteStatus::EmAnaliseAdmin => [
                'action' => 'em_analise_admin',
                'description' => "Orçamento enviado: R$ " . number_format($quote->price, 2, ',', '.') . ". Aguardando aprovação da plataforma.",
            ],
            QuoteStatus::Respondido => [
                'action' => 'respondido',
                'description' => "Aprovado pela plataforma. Orçamento: R$ " . number_format($quote->price, 2, ',', '.'),
            ],
            QuoteStatus::Aceito => [
                'action' => 'aceito',
                'description' => "Negócio fechado! {$quote->storageRequest->quantity} paletes reservados.",
            ],
            QuoteStatus::Rejeitado => [
                'action' => 'rejeitado',
                'description' => "Oferta recusada. Motivo: {$quote->rejection_reason}",
            ],
            default => null,
        };

        if ($actionData !== null) {
            QuoteHistory::create([
                'quote_id' => $quote->id,
                'company_id' => $company->id,
                'action' => $actionData['action'],
                'description' => $actionData['description'],
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);
        }
    }

    private function createPaymentForQuote(Quote $quote, Company $payer, Company $spaceOwner, int $daysAgo): Payment
    {
        $amount = (float) $quote->price;
        $feePercentage = config('payment.platform_fee_percentage', 10);
        $fee = round(($amount * $feePercentage) / 100, 2);
        $netAmount = round($amount - $fee, 2);

        // Variar status de payment para testar diferentes cenários
        $paymentStatuses = [
            PaymentStatus::Pending->value => 40,    // 40% - aguardando pagamento
            PaymentStatus::Processing->value => 20, // 20% - processando
            PaymentStatus::Paid->value => 30,        // 30% - pagos
            PaymentStatus::Failed->value => 10,      // 10% - falhados
        ];
        
        $statusValue = $this->weightedRandom($paymentStatuses);
        $status = PaymentStatus::from($statusValue);
        
        // Variar métodos de pagamento
        $methods = [PaymentMethod::Pix, PaymentMethod::CreditCard, PaymentMethod::Boleto];
        $method = $status !== PaymentStatus::Pending ? $methods[array_rand($methods)] : null;

        // Calcular datas baseado no status
        $createdAt = Carbon::now()->subDays($daysAgo);
        $paidAt = null;
        $expiresAt = null;

        if ($status === PaymentStatus::Processing || $status === PaymentStatus::Paid) {
            if ($method) {
                $expirationDays = $method->expirationDays();
                $expiresAt = $createdAt->copy()->addDays($expirationDays);
            }
        }

        if ($status === PaymentStatus::Paid) {
            $paidAt = $createdAt->copy()->addDays(rand(1, 3));
        }

        $payment = Payment::create([
            'quote_id' => $quote->id,
            'company_id' => $payer->id,
            'space_owner_id' => $spaceOwner->id,
            'amount' => $amount,
            'platform_fee' => $fee,
            'net_amount' => $netAmount,
            'payment_method' => $method,
            'status' => $status,
            'gateway' => 'stub',
            'gateway_transaction_id' => 'stub_' . $quote->id . '_' . time(),
            'gateway_response' => [
                'status' => $status->value,
                'method' => $method?->value,
            ],
            'payment_url' => $method === PaymentMethod::Pix || $method === PaymentMethod::Boleto 
                ? "https://stub-gateway.com/pay/{$quote->id}" 
                : null,
            'payment_code' => $method === PaymentMethod::Pix 
                ? $this->generatePixCode($amount) 
                : ($method === PaymentMethod::Boleto ? $this->generateBoletoCode() : null),
            'paid_at' => $paidAt,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Criar notificações de payment
        try {
            if ($status === PaymentStatus::Pending) {
                $payer->notify(new PaymentCreated($payment));
            } elseif ($status === PaymentStatus::Processing) {
                $payer->notify(new PaymentProcessing($payment));
            } elseif ($status === PaymentStatus::Paid) {
                $payer->notify(new PaymentConfirmed($payment));
                $spaceOwner->notify(new PaymentConfirmed($payment));
            } elseif ($status === PaymentStatus::Failed) {
                $payer->notify(new PaymentFailed($payment));
            }
        } catch (\Exception $e) {
            // Ignora erro de notificação no seed
        }

        return $payment;
    }

    private function createNotifications(Quote $quote, QuoteStatus $status, Company $buyer, Company $spaceOwner)
    {
        try {
            match($status) {
                QuoteStatus::Solicitado => $spaceOwner->notify(
                    new QuoteStatusChanged("Nova solicitação de cotação!", 'solicitado', $quote)
                ),
                QuoteStatus::EmAnaliseAdmin => null, // Admin é notificado via QuotePendingAdminReviewNotification no fluxo real
                QuoteStatus::Respondido => $buyer->notify(
                    new QuoteStatusChanged("Você recebeu um novo orçamento!", 'respondido', $quote)
                ),
                QuoteStatus::Aceito => $spaceOwner->notify(
                    new QuoteStatusChanged("Seu orçamento foi ACEITO!", 'aceito', $quote)
                ),
                QuoteStatus::Rejeitado => $buyer->notify(
                    new QuoteStatusChanged("Sua solicitação foi recusada.", 'rejeitado', $quote)
                ),
                default => null,
            };
        } catch (\Exception $e) {
            // Ignora erro de notificação no seed
        }
    }

    /**
     * Seleciona um item aleatório baseado em pesos
     */
    private function weightedRandom(array $weights)
    {
        $total = array_sum($weights);
        $random = rand(1, $total);
        $current = 0;

        foreach ($weights as $item => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return $item;
            }
        }

        return array_key_first($weights);
    }

    /**
     * Gera código PIX fictício para seed
     */
    private function generatePixCode(float $amount): string
    {
        // Gera código PIX fictício para seed
        $amountFormatted = str_pad((int)($amount * 100), 10, '0', STR_PAD_LEFT);
        return '00020126360014BR.GOV.BCB.PIX0114+5511999999999020400005303986540' . 
               $amountFormatted . '5802BR5925FICA FRIO LOGISTICA6009SAO PAULO62070503***6304' . 
               str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Gera linha digitável de boleto fictícia para seed
     */
    private function generateBoletoCode(): string
    {
        // Gera linha digitável fictícia para seed
        return '34191.09008 01234.567890 12345.678901 2 987600000' . rand(1000, 9999);
    }
}
