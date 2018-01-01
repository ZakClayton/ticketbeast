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
        $savedRequest = $this->app['request'];
        $this->json('POST',"/concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $savedRequest;
    }

    private function assertValidationError($field) {
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($field, $this->decodeResponseJson());
    }

    /** @test */
    function customerCanPurchaseConcertTicketsToAPublishedConcertTest() {
        // Arrange
        // Create a concert
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);

        // Act
        // Purchase Concert Tickets
        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        // Assert
        $this->assertResponseStatus(201);

        $this->seeJsonSubset([
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'amount' => 9750
        ]);

        // Make sure customer charged correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        // Make sure an order exists for customer
        $this->assertTrue($concert->hasOrderFor('test@example.com'));
        $this->assertEquals(3, $concert->ordersFor('test@example.com')->first()->ticketQuantity());

    }

    /** @test */
    function cannotPurchaseMoreTicketsThanRemain() {

        $concert = factory(Concert::class)->states('published')->create()->addTickets(50);

        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(422);
        $this->assertFalse($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    function cannotPurchaseTicketsAnotherCustomerIsAlreadyTryingToPurchase() {

        $this->disableExceptionHandling();

        $concert = factory(Concert::class)->states('published')->create(['ticket_price'=>1200])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {

            $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ]);

            $this->assertResponseStatus(422);
            $this->assertFalse($concert->hasOrderFor('personB@example.com'));
            $this->assertEquals(0, $this->paymentGateway->totalCharges());

        });

        $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertEquals(3600, $this->paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('personA@example.com'));
        $this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());

    }

    /** @test */
    function cannotPurchaseTicketsToAnUnpublishedConcert() {

        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(404);
        $this->assertFalse($concert->hasOrderFor('test@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    function anOrderIsNotCreatedIfPaymentFails() {

        $this->disableExceptionHandling();
        // Arrange
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);

        // Act
        $this->orderTickets($concert, [
            'email' => 'test@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        // Assert
        $this->assertResponseStatus(422);
        $this->assertFalse($concert->hasOrderFor('test@example.com'));
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
