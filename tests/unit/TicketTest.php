<?php

use App\Concert;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketTest extends TestCase {

    use DatabaseMigrations;

    /** @test */
    function ticketsCanBeReserved() {

        $ticket = factory(Ticket::class)->create();
        $this->assertNull($ticket->reserved_at);

        $ticket->reserve();

        $this->assertNotNull($ticket->fresh()->reserved_at);
    }

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
