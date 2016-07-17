<?php

class Seatplan {
    private $event = null;
    private $seatTable = 'forge_events_seats';
    private $seats = array();
    private $seatStatus = array(
        0 => 'available',
        1 => 'blocked',
        2 => 'spacer'
    );

    public function __construct($id) {
        $this->event = $id;

        $db = App::instance()->db;
        $db->where('event', $this->event);
        $this->seats = $db->get($this->seatTable);
    }

    public function draw() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/", "seatplan", array(
            'amount' => sprintf(i('Amount of Seats: %s'), $this->getSeatAmount()),
            'status_list' => $this->getAllStatus(),
            'event_id' => $this->event,
            'api_url' => Utils::getUrl(array("api", "forge-events", "seatplan", "toggle-seat")),
            'column_names' => $this->getRow(0),
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
            array_push($stats, array(
                'status' => $stat,
                'name' => i($stat)
            ));
        }
        return $stats;
    }

    public function getSeatRows() {
        $rowAmount = 60;
        $rows = array();
        for($count = 1; $count <= $rowAmount; $count++) {
            $rows[$count] = self::getRow($count);
        }
        return $rows;
  }

    public function getRow($no) {
        $columnAmount = 40;
        $name = 'A';
        $columns = array();
        for($count = 1; $count <= $columnAmount; $count++) {
            $columns[$name] = array('status' => $this->getSeatStatus($name, $no));
            $name++;
        }
        return $columns;
    }

    public function getSeatStatus($x, $y) {
        foreach($this->seats as $seat) {
            if($seat['x'] == $x && $seat['y'] == $y) {
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

    public function toggleSeat($seat) {
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