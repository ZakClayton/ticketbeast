<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use Carbon\Carbon;

class ViewConcertListingTest extends TestCase
{

//    function user_can_view_a_concert_listing() {
//
//        // Arrange ( Direct Model Access )
//
//        // Act
//
//        // Assert
//
//    }

    use DatabaseMigrations;

    /**
     * User can view a concert listing
     *
     * @test
     */
    public function userCanViewAPublishedConcertListingTest() {

        // Arrange
        // Create a concert
        $concert = factory(Concert::class)->states('published')->create([
            'title' => 'The Red Chord',
            'subtitle' => 'with Animosity and Lethargy',
            'date' => Carbon::parse('December 1, 2017 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Example Lane',
            'city' => 'Laravel',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'For tickets, call 0880 000 000',
            'published_at' => Carbon::parse('-1 week'),
        ]);

        // Act
        // View the concert listing
        $this->visit('/concerts/'.$concert->id);

        // Assert
        // See the concert details
        $this->see('The Red Chord');
        $this->see('with Animosity and Lethargy');
        $this->see('December 1, 2017');
        $this->see('8:00pm');
        $this->see('32.50');
        $this->see('The Mosh Pit');
        $this->see('123 Example Lane');
        $this->see('Laravel ON 17916');
        $this->see('For tickets, call 0880 000 000');

        return;
    }

    /** @test */
    function userCannotViewUnpublishedConcertListingsTest() {
        //Arrange
        $concert = factory(Concert::class)->states('unpublished')->create([]);
        //Act
        $this->get('/concerts/'.$concert->id);
        //Assert
        $this->assertResponseStatus(404);
    }
}
