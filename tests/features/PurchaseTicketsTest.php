<?php

use App\Billing\PaymentGateway;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Billing\FakePaymentGateway;
use App\Concert;


class PurchaseTicketsTest extends TestCase
{

    use DatabaseMigrations;

    protected $paymentGateway;

    protected function setUp() {
        parent::setUp();
        $this->paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    private function orderTickets($concert, $params) {
        $this->json('POST',"/concerts/{$concert->id}/orders", $params);
    }

    private function assertValidationError($field) {
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($field, $this->decodeResponseJson());
    }

    /** @test */
    function customerCanPurchaseConcertTicketsToAPublishedConcertTest() {
        // Arrange
        // Create a concert
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250]);

        // Act
        // Purchase Concert Tickets
        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        // Assert
        $this->assertResponseStatus(201);
        // Make sure customer charged correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        // Make sure an order exists for customer
        $order = $concert->orders()->where('email','test@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());

    }

    /** @test */
    function cannotPurchaseMoreTicketsThanRemain() {

    }

    /** @test */
    function cannotPurchaseTicketsToAnUnpublishedConcert() {

        $concert = factory(Concert::class)->states('unpublished')->create();

        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(404);

        $this->assertEquals(0, $concert->orders()->count());

        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    function anOrderIsNotCreatedIfPaymentFails() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250]);
        // Act
        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);
        // Assert
        $this->assertResponseStatus(422);
        $order = $concert->orders()->where('email','test@example.com')->first();
        $this->assertNull($order);
    }

    /** @test */
    function emailIsRequiredToPurchaseTicketsTest() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create();
        // Act
        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        // Assert
        $this->assertValidationError('email');
    }

    /** @test */
    function emailIsAValidEmailAddressTest() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create();
        // Act
        $this->orderTickets($concert, [
            'email' => 'jane-example-not-an-email',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        // Assert
        $this->assertValidationError('email');
    }

    /** @test */
    function ticketQuantityIsRequiredFieldTest() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create();
        // Act
        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        // Assert
        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    function ticketQuantityMustBeAtLeastOneToPurchaseTicketsTest() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create();
        // Act
        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        // Assert
        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    function paymentTokenIsRequiredTest() {
        // Arrange
        $concert = factory(Concert::class)->states('published')->create();
        // Act
        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 0,
        ]);
        // Assert
        $this->assertValidationError('payment_token');
    }

}
