<?php

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @class ConcertTest
 */
class ConcertTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    function canGetFormattedDateTest()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('December 2, 2017 8:00pm')
        ]);

        $this->assertEquals('December 2, 2017', $concert->formattedDate);
    }

    /** @test */
    function canGetFormattedStartTimeTest() {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('December 2, 2017 17:00:00')
        ]);

        $this->assertEquals('5:00pm', $concert->formattedStartTime);
    }

    /** @test */
    function canGetTicketPriceInDollarsTest() {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750
        ]);

        $this->assertEquals('67.50', $concert->ticketPriceInDollars);
    }

    /** @test */
    function concertsWithAPublishedAtDateArePublishedTest() {
        $publishedConcertA = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $publishedConcertB = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $unpublishedConcert = factory(Concert::class)->create(['published_at' => null]);

        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    function canOrderConcertTicketsTest() {
        // Arrange
        $concert = factory(Concert::class)->create()->addTickets(3);
        // Act
        $order = $concert->orderTickets('jane@example.com', 3);
        // Assert
        $this->assertEquals('jane@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
    }

    /** @test */
    function canAddTicketsTest() {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(50);
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    function ticketsRemainingDoesNotIncludeTicketsAssociatedWithAnOrderTest() {
        $concert = factory(Concert::class)->create()->addTickets(50);
        $concert->orderTickets('jane@example.com', 30);

        $this->assertEquals(20, $concert->ticketsRemaining());

    }

    /** @test */
    function tryingToPurchaseMoreTicketsThanRemainThrowsAnException() {

        $concert = factory(Concert::class)->create()->addTickets(10);

        try {
            $concert->orderTickets('jane@example.com', 11);
        } catch (NotEnoughTicketsException $e) {
            $order = $concert->orders()->where('email', 'jane@example.com')->first();
            $this->assertFalse($concert->hasOrderFor('jane@example.com'));
            return ;
        }

        $this->fail("Order succeeded even though not enough tickets remaining.");
    }

    /** @test */
    function cannotOrderTicketsThatCanAlreadyBeenPurchasedTest() {
        $concert = factory(Concert::class)->create()->addTickets(10);
        $concert->orderTickets('jane@example.com', 8);

        try {
            $concert->orderTickets('jon@example.com', 5);
        } catch(NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('jon@example.com'));
            $this->assertEquals(2, $concert->ticketsRemaining());
            return ;
        }

        $this->fail();
    }

}
