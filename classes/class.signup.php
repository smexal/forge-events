<?php

class Signup {
    private $steps = array();
    private $event = false;

    public function __construct($event) {
        $this->event = $event;

        $this->steps['user'] = new SignupStepUser();
        $this->steps['buy'] = new SignupStepBuy($this->event);
        $this->steps['seat'] = new SignupStepSeat($this->event);
    }

    public function getContents() {
        $contents = array();
        foreach($this->steps as $name => $step) {
            array_push($contents, array(
                'id' => $step->id,
                'active' => $this->getActive() == $name ? true : false,
                'content' => $step->content()
            ));
        }
        return $contents;
    }

    public function getSteps() {
        $contents = array();
        foreach($this->steps as $name => $step) {
            array_push($contents, 
                array(
                'active' => $this->getActive() == $name ? true : false,
                'disabled' => ! $step->allowed(),
                'title' => $step->title(),
                'id' => $step->id
            ));
        }
        return $contents;
    }

    private function getActive() {
        if( $this->steps['user']->allowed() && 
            $this->steps['buy']->allowed() && 
            $this->steps['seat']->allowed()) {
            return 'seat';
        }
        if( $this->steps['user']->allowed() && 
            $this->steps['buy']->allowed() && 
            ! $this->steps['seat']->allowed()) {
            return 'buy';
        }
        if( $this->steps['user']->allowed() && 
            ! $this->steps['buy']->allowed() && 
            ! $this->steps['seat']->allowed()) {
            return 'user';
        }
    }

}

?>