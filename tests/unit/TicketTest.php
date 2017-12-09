<?php

use App\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketTest extends TestCase {

    use DatabaseMigrations;

    /** @test */
    function aTicketCanBeReleased() {

        $concert = factory(Concert::class)->create();

        $concert->addTickets(1);

        $order = $concert->orderTickets('jane@example.com', 1);

        $ticket = $order->tickets()->first();

        $this->assertEquals($order->id, $ticket->order_id);


        $ticket->release();


        $this->assertNull($ticket->fresh()->order_id);

    }

}
