<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\App\App;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;
use \Forge\Core\Classes\Settings;

use \Forge\Modules\ForgePayment\Payment;



class SignupStepSeat {
    public $id = 'signup-seat';
    private $event = false;

    public function __construct($event) {
        $this->event = $event;
    }

    public function content() {
        if($this->allowed()) {
            return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "seat", array(
                'buyed_title' => i('Your purchased seats'),
                'buyed_description' => i('Choose the seat you want to set in the seatplan, by checking the radio box.'),
                'buyed_seats' => $this->getBuyedSeats(),
                'seatplan_title' => i('Seatplan'),
                'seatplan_description' => i('Make sure, you have checked the correct user in the list above, then click on an available seat to set your reservation.'),
                'seatplan' => $this->seatplan(),
                'completeReservation' => [
                    'text' => i('Complete reservation', 'forge-events'),
                    'link' => Utils::url(['event-signup', $this->event->slug(), 'complete-order'])
                ],
                'locked_text' => i('seat is locked', 'forge-events')
            ));
        } else {
            return '';
        }
    }

    public function getBuyedSeats() {
        $orders = Payment::getPayments(App::instance()->user->get('id'));
        $buyed_seats = array();
        foreach($orders as $order) {
            foreach($order['meta']->items as $item) {
                if($item->collection == $this->event->id) {
                    if(! User::exists($item->user)) 
                        continue;
                    $user = new User($item->user);
                    array_push($buyed_seats, array(
                        "user" => $user->get('username') .' <small>'. $this->getUserSeat($user->get('id')).'</small>',
                        "id" => $item->user,
                        "seatSet" => $this->getUserSeat($user->get('id')) ? true : false,
                        'seatId' => $this->getUserSeatId($user->get('id')),
                        'locked' => $this->seatLocked($user->get('id'))
                    ));
                }
            }
        }
        // check for own seat, buyed from someone else...
        $buyedSelf = false;
        foreach($buyed_seats as $s) {
            if($s['id'] == App::instance()->user->get('id')) {
                $buyedSelf = true;
                break;
            }
        }
        if(! $buyedSelf) {
            $collection = $this->event->getCollection();
            $user = App::instance()->user;
            if (! $collection->userTicketAvailable($this->event->id, $user->get('id'))) {
                $buyed_seats[] = [
                    "user" => $user->get('username') .' <small>'. $this->getUserSeat($user->get('id')).'</small>',
                    "id" => $user->get('id'),
                    "seatSet" => $this->getUserSeat($user->get('id')) ? true : false,
                    'seatId' => $this->getUserSeatId($user->get('id')),
                    'locked' => $this->seatLocked($user->get('id'))
                ];
            }
        }
        return $buyed_seats;
    }

    public function seatLocked($user) {
        if(! Settings::get('forge-events-seatplan-locked')) {
            return false;
        }
        $db = App::instance()->db;
        if(is_object($this->event)) {
            $id = $this->event->id;
        } else {
            $id = $this->event;
        }
        $db->where('event_id', $id);
        $db->where('user', $user);
        $seat = $db->getOne('forge_events_seat_reservations');
        return $seat['locked'];
    }

    public function getUserSeatId($user) {
        $db = App::instance()->db;
        if(is_object($this->event)) {
            $id = $this->event->id;
        } else {
            $id = $this->event;
        }
        $db->where('event_id', $id);
        $db->where('user', $user);
        $seat = $db->getOne('forge_events_seat_reservations');
        return $seat['id'];
    }

    public function getUserSeat($user) {
        $db = App::instance()->db;
        if(is_object($this->event)) {
            $id = $this->event->id;
        } else {
            $id = $this->event;
        }
        $db->where('event_id', $id);
        $db->where('user', $user);
        $seat = $db->get('forge_events_seat_reservations');
        if(count($seat) > 0) {
            return sprintf(i('<strong>%s</strong>'), $seat[0]['x'].':'.$seat[0]['y']);
        }
        return '';
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
