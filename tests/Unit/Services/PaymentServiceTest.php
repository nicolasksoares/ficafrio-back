<?php

namespace Tests\Unit\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\QuoteStatus;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Exceptions\PaymentAlreadyExistsException;
use App\Exceptions\PaymentCannotBeProcessedException;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentGatewayInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_fee_returns_10_percent_by_default(): void
    {
        $service = new PaymentService($this->gateway);
        $this->assertEquals(100.00, $service->calculateFee(1000.00));
    }

    public function test_calculate_net_amount_subtracts_fee_from_amount(): void
    {
        $service = new PaymentService($this->gateway);
        $this->assertEquals(900.00, $service->calculateNetAmount(1000.00, 100.00));
    }

    public function test_create_payment_throws_if_quote_already_has_payment(): void
    {
        $this->expectException(PaymentAlreadyExistsException::class);

        [$client, $partner, $request, $space] = $this->createScenario();
        $quote = $this->createAcceptedQuote($client, $partner, $request, $space, 1500.00);

        Payment::create([
            'quote_id' => $quote->id,
            'company_id' => $client->id,
            'space_owner_id' => $partner->id,
            'amount' => 1500,
            'platform_fee' => 150,
            'net_amount' => 1350,
            'status' => PaymentStatus::Pending,
        ]);
        $quote->refresh();

        $service = new PaymentService($this->gateway);
        $service->createPayment($quote);
    }

    public function test_create_payment_throws_if_quote_expired(): void
    {
        $this->expectException(PaymentCannotBeProcessedException::class);
        $this->expectExceptionMessage('Esta cotação expirou');

        [$client, $partner, $request, $space] = $this->createScenario();
        $quote = $this->createAcceptedQuote($client, $partner, $request, $space, 1500.00);
        $quote->update(['valid_until' => now()->subDay()]);

        $service = new PaymentService($this->gateway);
        $service->createPayment($quote->fresh());
    }

    public function test_create_payment_throws_if_quote_has_invalid_price(): void
    {
        $this->expectException(PaymentCannotBeProcessedException::class);
        $this->expectExceptionMessage('valor válido');

        [$client, $partner, $request, $space] = $this->createScenario();
        $quote = $this->createAcceptedQuote($client, $partner, $request, $space, 0);

        $service = new PaymentService($this->gateway);
        $service->createPayment($quote->fresh());
    }

    public function test_create_payment_creates_payment_and_updates_quote(): void
    {
        [$client, $partner, $request, $space] = $this->createScenario();
        $quote = $this->createAcceptedQuote($client, $partner, $request, $space, 1500.00);

        $service = new PaymentService($this->gateway);
        $payment = $service->createPayment($quote);

        $this->assertNotNull($payment->id);
        $this->assertEquals(1500.00, $payment->amount);
        $this->assertEquals(150.00, $payment->platform_fee);
        $this->assertEquals(1350.00, $payment->net_amount);
        $this->assertEquals(PaymentStatus::Pending->value, $payment->status->value);

        $quote->refresh();
        $this->assertEquals($payment->id, $quote->payment_id);
    }

    private function createScenario(): array
    {
        $client = Company::factory()->create(['type' => UserType::Cliente]);
        $partner = Company::factory()->create(['type' => UserType::Cliente]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 100,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'SP',
            'target_state' => 'SP',
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara A',
            'zip_code' => '000',
            'address' => 'R',
            'number' => '1',
            'district' => 'D',
            'city' => 'SP',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
        ]);

        return [$client, $partner, $request, $space];
    }

    private function createAcceptedQuote(
        Company $client,
        Company $partner,
        StorageRequest $request,
        Space $space,
        float $price
    ): Quote {
        return Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'price' => $price,
            'valid_until' => now()->addDays(7),
            'status' => QuoteStatus::Aceito,
        ]);
    }
}
