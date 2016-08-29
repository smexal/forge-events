<?php

class Seatplan {
    private $event = null;
    private $seatTable = 'forge_events_seats';
    private $seats = array();
    private $trim = false;
    private $db = null;
    private $soldSeats = null;
    private $seatStatus = array(
        0 => 'available',
        1 => 'blocked',
        2 => 'spacer',
        3 => 'sold'
     );
    /**
     * Help the translation crawler...
     * i('available');
     * i('blocked');
     * i('spacer');
     * i('sold');
     */

    public function __construct($id, $trim = false) {
        $this->event = $id;
        $this->trim = $trim;

        $this->db = App::instance()->db;
        $this->db->where('event', $this->event);
        $this->seats = $this->db->get($this->seatTable);
    }

    public function draw() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/", "seatplan", array(
            'trim' => $this->trim,
            'amount' => sprintf(i('Amount of Seats: %s'), $this->getSeatAmount()),
            'status_list' => $this->getAllStatus(),
            'event_id' => $this->event,
            'api_url' => Utils::getUrl(array("api", "forge-events", "seatplan", "toggle-seat")),
            'column_names' => $this->getRow(1),
            'rows' => $this->getSeatRows()
        ));
    }

    private function getSeatAmount() {
        $amount = 0;
        foreach($this->seats as $seat) {
            if($seat['type'] == 'available' || $seat['type'] == 'blocked') {
                $amount++;
            }
        }
        return $amount;
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
        $rowAmount = 60;
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
        $columnAmount = 40;
        $columns = array();
        for($count = 1; $count <= $columnAmount; $count++) {
            $status = $this->getSeatStatus($name, $no);
            if($this->trim) {
                if($status !== 'undefined') {
                    $columns[$name] = array(
                        'status' => $status,
                        'tooltip' => $this->getTooltip($name, $no, $status)
                    );
                }
            } else {
                $columns[$name] = array(
                    'status' => $status,
                    'tooltip' => $this->getTooltip($name, $no, $status)
                );
            }
            $name++;
        }
        return $columns;
    }

    private function getTooltip($name, $no, $status) {
        if($status == 'spacer') {
            return false;
        }
        $user = false;
        if($status == 'sold') {
            $user = $this->getSoldUser($name, $no);
        }
        if($user) {
            $status = sprintf(i('Sold to %s', 'forge-events'), $user->get('username'));
        }
        return $name.':'.$no.' - '.i($status);
    }

    public function getSoldUser($x, $y) {
        $db = App::instance()->db;
        $db->where('event_id', $this->event);
        $db->where('x', $x);
        $db->where('y', $y);
        $seat = $db->getOne('forge_events_seat_reservations');
        if($seat) {
            return new User($seat['user']);
        }
        return false;
    }

    public function getSeatStatus($x, $y) {
        if(is_null($this->soldSeats)) {
            $db = App::instance()->db;
            $db->where('event_id', $this->event);
            $this->soldSeats = $db->get('forge_events_seat_reservations');
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
            default:
                return;
        }
    }

    public function saveReservation($user, $x, $y, $order, $event) {
        $db = App::instance()->db;
        // check if user already has a reservation, if yes. remove it.
        $db->where('user', $user);
        $db->where('event_id', $event);
        $db->get('forge_events_seat_reservations');
        if($db->count > 0) {
            $db->where('user', $user);
            $db->where('event_id', $event);
            $db->delete('forge_events_seat_reservations');
        }

        $db->insert('forge_events_seat_reservations', array(
            "user" => $user,
            "x" => $x,
            "y" => $y,
            "order_id" => $order,
            "event_id" => $event
        ));

        $db->where('event_id', $this->event);
        $this->soldSeats = $db->get('forge_events_seat_reservations');
    }

    public function toggleSeat($seat) {

        // reservation
        if($seat['reservation']) {
            if($this->getSeatStatus($seat['x'], $seat['y']) != 'available') {
                $this->trim = true;
                return json_encode(array( "plan" => $this->draw()));
            }

            $payments = Payment::getPayments($seat['reservation']);
            foreach($payments as $payment) {
                if($payment['collection_item'] != $seat['event'])
                    continue;

                $this->saveReservation($seat['reservation'], $seat['x'], $seat['y'], $payment['id'], $seat['event']);
            }
        }
        if(! Auth::allowed('manage.forge-events', false) || $seat['reservation']) {
            $newSeatplan = new Seatplan($seat['event'], true);
            return json_encode(array( "plan" => $newSeatplan->draw()));
        }

        // admin toggle
        $db = App::instance()->db;

        $db->where('x', $seat['x']);
        $db->where('y', $seat['y']);
        $data = $db->getOne($this->seatTable);
        if(count($data) > 0) {
            $db->where('id', $data['id']);
            $status = $this->getNextSeatStatus($data['type']);
            if(!is_null($status)) {
                $db->update($this->seatTable, array(
                    'type' => $status
                ));
            } else {
                $db->where('id', $data['id']);
                $db->delete($this->seatTable);
            }
            $newSeatplan = new Seatplan($data['event']);
            return json_encode(array( "plan" => $newSeatplan->draw()));
        } else {
            $db->insert($this->seatTable, array(
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
        if(array_key_exists($id, $this->seatStatus)) {
            return $this->seatStatus[$id];
        } else {
            return null;
        }
    }

}

?>