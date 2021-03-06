<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Reservation;
use Illuminate\Http\Request;

class ConcertOrdersController extends Controller
{

    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId) {

        $concert = Concert::published()->findOrFail($concertId);

        $this->validate(request(), [
            'email' => ['required','email'],
            'ticket_quantity' => ['min:1','integer','required'],
            'payment_token' => ['required']
        ]);

        try {

            // ToDo: Find some tickets
            $tickets = $concert->reserveTickets(request('ticket_quantity'));
            $reservation = new Reservation($tickets);

            // ToDo: Charge te customer
            $this->paymentGateway->charge($reservation->totalCost(), request('payment_token'));

            // ToDo: Create an order for those tickets.
            $order = Order::forTickets($tickets, request('email'), $reservation->totalCost());

            return response()->json($order, 201);

        } catch (PaymentFailedException $e) {
            return response()->json([],422);
        } catch (NotEnoughTicketsException $e) {
            return response()->json([],422);
        }

    }

}
