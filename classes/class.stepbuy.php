<?php

class SignupStepBuy {
    public $id = 'signup-buy';
    private $event = false;

    public function __construct($event) {
        $this->event = $event;
    }

    public function content() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "buy", array(
            'title' => i('Your Tickets', 'forge-events'),
            'tr' => $this->getTr(),
            'td' => $this->getTd()
        ));
    }

    public function getTd() {
        $rows = array();
        array_push($rows, array(
            'available', 
            App::instance()->user->get('username').' ('.App::instance()->user->get('email').')', 
            'default', 
            Utils::formatAmount($this->event->getMeta('price'))
        ));
        return $rows;
    }

    public function getTr() {
        return array(
            array(
                'name' => i('Status', 'forge-events')
            ),
            array(
                'name' => i('User', 'forge-events'),
            ),
            array(
                'name' => i('Ticket Type', 'forge-events')
            ),
            array(
                'name' => i('Price', 'forge-events')
            )
        );
    }

    public function title() {
        return i('2. Buy Tickets', 'forge-events');
    }

    public function allowed() {
        if(is_null(App::instance()->user)) {
            return false;
        }
        if(App::instance()->user->get('active') == 0) {
            return false;
        }
        return true;
    }


}

?>