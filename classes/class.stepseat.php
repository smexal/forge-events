<?php

class SignupStepSeat {
    public $id = 'signup-seat';
    private $event = false;

    public function __construct($event) {
        $this->event = $event;
    }

    public function content() {
        return 'seat';
    }

    public function title() {
        return i('3. Choose seat', 'forge-events');
    }

    public function allowed() {
        return false;
    }


}

?>