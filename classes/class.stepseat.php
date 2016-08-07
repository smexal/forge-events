<?php

class SignupStepSeat {
    public $id = 'signup-seat';
    private $event = false;

    public function __construct($event) {
        $this->event = $event;
    }

    public function content() {
        if($this->allowed()) {
            return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "seat", array(
                'buyed_title' => i('Your buyed seats'),
                'buyed_description' => i('Choose the seat you want to set in the seatplan, by checking the radio box.'),
                'buyed_seats' => $this->getBuyedSeats(),
                'seatplan_title' => i('Seatplan'),
                'seatplan_description' => i('Make sure, you have checked the correct user in the list above, then click on a available seat to set your reservation.'),
                'seatplan' => $this->seatplan()
            ));
        } else {
            return '';
        }
    }

    public function getBuyedSeats() {
        $orders = Payment::getPayments(App::instance()->user->get('id'));
        $buyed_seats = array();
        foreach($orders as $order) {
            if($order['collection_item'] == $this->event->id) {
                $user = new User($order['user']);
                array_push($buyed_seats, array(
                    "user" => $user->get('username'),
                    "id" => $user->get('id')
                ));
            }
        }
        return $buyed_seats;
    }

    public function seatplan() {
        $seatplan = new Seatplan($this->event->id, true);
        return $seatplan->draw();
    }

    public function title() {
        return i('3. Choose seat', 'forge-events');
    }

    public function allowed() {
        $collection = $this->event->getCollection();
        if(App::instance()->user) {
            $userid = App::instance()->user->get('id');
            if($collection->userTicketAvailable($this->event->id, $userid)) {
                return false;
            } else {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }


}

?>