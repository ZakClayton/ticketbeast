<?php

use App\Concert;
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
    function canGetTicketPriceInDollars() {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750
        ]);

        $this->assertEquals('67.50', $concert->ticketPriceInDollars);
    }

    /** @test */
    function concertsWithAPublishedAtDateArePublished() {
        $publishedConcertA = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $publishedConcertB = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $unpublishedConcert = factory(Concert::class)->create(['published_at' => null]);

        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    function canOrderConcertTickets() {

        // Arrange
        $concert = factory(Concert::class)->create([]);
        // Act
        $order = $concert->orderTickets('jane@example.com',3);
        // Assert
        $this->assertEquals('jane@example.com',$order->email);
        $this->assertEquals(3,$order->tickets()->count());

    }

}
