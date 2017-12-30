<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\App\App;
use \Forge\Core\App\Auth;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;
use \Forge\Core\Classes\Logger;

use \Forge\Modules\ForgePayment\Payment;



class Seatplan {
    private $event = null;
    private $collection = null;
    private $seatTable = 'forge_events_seats';
    private $seats = array();
    private $trim = false;
    private $db = null;
    private $soldSeats = null;
    public $actions = true;
    private $seatStatus = array(
        0 => 'available',
        1 => 'blocked',
        2 => 'spacer',
        3 => 'sold'
     );
    /**
     * Help the translation crawler...
     * i('available', 'core');
     * i('blocked', 'core');
     * i('spacer', 'core');
     * i('sold', 'core');
     */

    public function __construct($id, $trim = false) {
        $this->event = $id;
        $this->trim = $trim;
        $this->collection = App::instance()->cm->getCollection('forge-events')->getItem($this->event);

        $this->db = App::instance()->db;
        $this->db->where('event', $this->event);
        $this->seats = $this->db->get($this->seatTable);
    }

    public function draw() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/", "seatplan", array(
            'trim' => $this->trim,
            'amount' => sprintf(i('Available: %s', 'forge-events'), $this->getSeatDisplay()),
            'status_list' => $this->getAllStatus(),
            'event_id' => $this->event,
            'api_url' => Utils::getUrl(array("api", "forge-events", "seatplan", "toggle-seat")),
            'column_names' => $this->getRow(1),
            'rows' => $this->getSeatRows(),
            'actions' => $this->actions
        ));
    }

    public function getSeatDisplay() {
        return ($this->getSeatAmount() - $this->getSoldAmount()).' ( '.i('Total: ', 'forge-events').' '.$this->getSeatAmount().' )';
    }

    public function getSeatAmount() {
        $amount = 0;
        foreach($this->seats as $seat) {
            if($seat['type'] == 'available' || $seat['type'] == 'blocked' || $seat['type'] == 'sold') {
                $amount++;
            }
        }
        return $amount;
    }

    public function getSoldAmount() {
        $amount = 0;
        $countsAsSold = ['sold', 'blocked'];
        foreach($this->seats as $seat) {
            if(in_array($this->getSeatStatus($seat['x'], $seat['y']), $countsAsSold)) {
                $amount++;
            }
        }
        return $amount;
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
            return $seat[0]['x'].':'.$seat[0]['y'];
        }
        return '';
    }

    public static function getSeatId($seat, $eventId) {
        if(is_string($seat)) {
            $seat = explode(":", $seat);
        } else {
            $seat = [$seat['x'], $seat['y']];
        }
        App::instance()->db->where('x', $seat[0]);
        App::instance()->db->where('y', $seat[1]);
        App::instance()->db->where('event_id', $eventId);
        $dbSeat = App::instance()->db->getOne('forge_events_seat_reservations');
        if(is_array($dbSeat)) {
            return $dbSeat['id'];
        }
        return null;
    }

    private function getAllStatus() {
        $stats = array();
        foreach($this->seatStatus as $stat) {
            if($this->trim) {
                if($stat != 'spacer') {
                    array_push($stats, array(
                        'status' => $stat,
                        'name' => i($stat)
                    ));
                }
            } else {
                array_push($stats, array(
                    'status' => $stat,
                    'name' => i($stat)
                ));
            }
        }
        return $stats;
    }

    public function getSeatRows() {
        $rowAmount = $this->collection->getMeta('seatplan_rows');
        if(! $rowAmount)
            $rowAmount = 20;
        $rows = array();
        for($count = 1; $count <= $rowAmount; $count++) {
            $row = self::getRow($count);
            if(count($row) > 0) {
                $rows[$count] = $row;
            }
        }
        return $rows;
  }

    public function getRow($no) {
        $name = 'A';
        $columnAmount = $this->collection->getMeta('seatplan_columns');
        if(! $columnAmount )
            $columnAmount = 30;

        $columns = array();
        for($count = 1; $count <= $columnAmount; $count++) {
            $status = $this->getSeatStatus($name, $no);
            if($status !== 'undefined' || $this->trim == false) {
                if($status == 'spacer' || $status == 'undefined') {
                    $columns[$name] = array(
                        'status' => $status,
                        'tooltip' => false,
                        'user' => false
                    );
                } else {
                    $seatInfo = $this->getSeatInfo($name, $no, $status);
                    $columns[$name] = array(
                        'status' => $status,
                        'tooltip' => $seatInfo['tooltip'],
                        'user' => $seatInfo['user']
                        /*'tipurl' => Utils::getUrl(array(
                            "api",
                            "forge-events",
                            "seatplan",
                            "seatstatus",
                            $this->event,
                            $name,
                            $no
                        ))*/
                    );
                }
            }
            $name++;
        }
        return $columns;
    }

    private function isUserSeat($x, $y) {
        if(App::instance()->user) {
            $ordersOfThisUser = Payment::getPayments(App::instance()->user->get('id'));
        } else {
            $ordersOfThisUser = [];
        }
        foreach($ordersOfThisUser as $order) {
            foreach($order['meta']->items as $item) {
                $this->db->where('user', $item->user);
                $this->db->where('event_id', $this->event);
                $seat = $this->db->getOne('forge_events_seat_reservations');
                if(count($seat) > 0) {
                    if($seat['x'] == $x && $seat['y'] == $y) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function getSeatInfo($name, $no, $status = false) {
        if(!$status) {
            $status = $this->getSeatStatus($name, $no);
        }
        if($status == 'spacer') {
            return false;
        }
        $user = false;
        if($status == 'sold') {
            $user = $this->getSoldUser($name, $no);
        }
        $username = false;
        if($user) {
            $status = sprintf(i('Sold to %s', 'forge-events'), $user->get('username'));
            $username = $user->get('username');
        }
        return [
            'tooltip' => $name.':'.$no.' - '.i($status),
            'user' => $username
        ];
    }

    public function getSoldUser($x, $y) {
        $this->db->where('event_id', $this->event);
        $this->db->where('x', $x);
        $this->db->where('y', $y);
        $seat = $this->db->getOne('forge_events_seat_reservations');
        if($seat) {
            return new User($seat['user']);
        }
        return false;
    }

    public function getSeatStatus($x, $y) {
        if(is_null($this->soldSeats)) {
            $this->db->where('event_id', $this->event);
            $this->soldSeats = $this->db->get('forge_events_seat_reservations');
        }
        foreach($this->seats as $seat) {
            if($seat['x'] == $x && $seat['y'] == $y) {
                if($seat['type'] == 'available') {
                    foreach($this->soldSeats as $sold) {
                        if($sold['x'] == $x && $sold['y'] == $y) {
                            return 'sold';
                        }
                    }
                    // check if seat is already sold.
                }
                return $seat['type'];
            }
        }
        return 'undefined';
    }

    public function handleRequest($query, $data) {
        array_shift($query);
        switch($query[0]) {
            case 'toggle-seat':
                return $this->toggleSeat($data);
            case 'seatstatus':
                $x = $query[2];
                $y = $query[3];
                return json_encode( array("content" => $this->getSeatInfo($x, $y)) );
            default:
                return;
        }
    }

    public function flushSeatReservation($x, $y, $event) {
        $this->db->where('event_id', $event);
        $this->db->where('x', $x);
        $this->db->where('y', $y);
        $this->db->delete('forge_events_seat_reservations');
    }

    public function saveReservation($user, $x, $y, $order, $event) {
        // check if user already has a reservation, if yes. remove it.
        $this->db->where('user', $user);
        $this->db->where('event_id', $event);
        $this->db->get('forge_events_seat_reservations');
        if($this->db->count > 0) {
            $this->db->where('user', $user);
            $this->db->where('event_id', $event);
            $this->db->delete('forge_events_seat_reservations');
        }

        $this->db->insert('forge_events_seat_reservations', array(
            "user" => $user,
            "x" => $x,
            "y" => $y,
            "order_id" => $order,
            "event_id" => $event
        ));

        $this->db->where('event_id', $this->event);
        $this->soldSeats = $this->db->get('forge_events_seat_reservations');
    }

    public function toggleSeat($seat) {

        // reservation
        if($seat['reservation'] == 'none') {
            $this->trim = true;
            return json_encode(array( "plan" => $this->draw()));
        }
        if($seat['reservation'] && $seat['reservation'] != 'admin') {
            if($this->getSeatStatus($seat['x'], $seat['y']) != 'available') {
                $this->trim = true;
                return json_encode(array( "plan" => $this->draw()));
            }

            $saved = false;
            $ordersOfThisUser = Payment::getPayments(App::instance()->user->get('id'));
            foreach($ordersOfThisUser as $order) {
                foreach($order['meta']->items as $item) {
                    if( $item->user == $seat['reservation'] && $item->collection == $seat['event']) {
                        $saved = true;
                        $this->saveReservation(
                            $seat['reservation'],
                            $seat['x'],
                            $seat['y'],
                            $order['id'],
                            $seat['event']
                        );
                    }
                }
            }
            // is own reservation
            $collection = App::instance()->cm->getCollection('forge-events');
            if($seat['reservation'] == App::instance()->user->get('id')
                && ! $collection->userTicketAvailable($seat['event'], $seat['reservation'])
                && ! $saved) {

                $order = $collection->getTicketOrder($seat['event'], $seat['reservation']);
                $this->saveReservation(
                    $seat['reservation'],
                    $seat['x'],
                    $seat['y'],
                    $order,
                    $seat['event']
                );
            }
        }
        if(! Auth::allowed('manage.forge-events', false) || $seat['reservation'] != 'admin') {
            $newSeatplan = new Seatplan($seat['event'], true);
            return json_encode(array( "plan" => $newSeatplan->draw()));
        }

        // admin toggle

        $this->db->where('x', $seat['x']);
        $this->db->where('y', $seat['y']);
        $data = $this->db->getOne($this->seatTable);
        if(count($data) > 0) {
            $this->db->where('id', $data['id']);
            $status = $this->getNextSeatStatus($data['type']);
            if(!is_null($status)) {
                $this->db->update($this->seatTable, array(
                    'type' => $status
                ));
                // remove existing reservations of a seat
                $this->flushSeatReservation($seat['x'], $seat['y'], $seat['event']);
            } else {
                $this->db->where('id', $data['id']);
                $this->db->delete($this->seatTable);
            }
            $newSeatplan = new Seatplan($data['event']);
            return json_encode(array( "plan" => $newSeatplan->draw()));
        } else {
            $this->db->insert($this->seatTable, array(
                'event' => $seat['event'],
                'x' => $seat['x'],
                'y' => $seat['y'],
                'type' => $this->seatStatus[0]
            ));
            $newSeatplan = new Seatplan($seat['event']);
            return json_encode(array( "plan" => $newSeatplan->draw()));
        }
    }

    private function getNextSeatStatus($type) {
        $id = array_search($type, $this->seatStatus);
        $id++;
        if (array_key_exists($id, $this->seatStatus)) {
            return $this->seatStatus[$id];
        } else {
            return null;
        }
    }
}

?>
