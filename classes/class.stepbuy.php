<?php

class SignupStepBuy {
    public $id = 'signup-buy';
    private $event = false;

    public function __construct($event) {
        if(is_numeric($event)) {
            $collection = App::instance()->cm->getCollection('forge-events');
            $this->event = $collection->getItem($event);
        }
        $this->event = $event;
    }

    public function content() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "buy", array(
            'title' => i('Your Tickets', 'forge-events'),
            'tr' => $this->getTr(),
            'td' => $this->getTd(),
            'other_user_title' => i('Buy for another user'),
            'other_user_desc' => i('Buy a ticket for another user, by typing a valid user\'s E-Mail.'),
            'other_user_url' => Utils::getUrl(array('api', 'forge-events', 'ticket-buy', $this->event->id, 'another-user')),
            'add_user_form' => $this->getUserAddInput()
        ));
    }

    public function addAnotherUser($usermail = false) {
        return json_encode(array("proceed" => $usermail));
    }

    private function getUserAddInput() {
        $form = Fields::text(array(
            'key' => 'buy-for-another-user',
            'label' => i('User E-Mail'),
        ));
        $form.= Fields::button(i('Add to list'), 'discreet');

        return $form;
    }

    private function getTotal() {
        return  Utils::formatAmount($this->event->getMeta('price'));
    }

    private function getTicketStatus($userid) {
        $collection = $this->event->getCollection();
        if($collection->userTicketAvailable($this->event->id, $userid)) {
            return '<span class="special">'.i('Available', 'forge-events').'</span>';
        } else {
            return '<span class="special">'.i('Purchased', 'forge-events').'</span>';
        }
    }

    public function getTd() {
        if(is_null(App::instance()->user)) {
            return '';
        }
        $rows = array();
        array_push($rows, array(
            $this->getTicketStatus(App::instance()->user->get('id')), 
            App::instance()->user->get('username').' ('.App::instance()->user->get('email').')', 
            'default', 
            Utils::formatAmount($this->event->getMeta('price')),
            $this->getAction(App::instance()->user->get('id'))
        ));
        return $rows;
    }

    public function getAction() {
        $collection = $this->event->getCollection();
        $userid = App::instance()->user->get('id');
        if($collection->userTicketAvailable($this->event->id, $userid)) {
            return '<a href="#" class="btn btn-discreet payment-trigger" 
                    data-redirect-success="'.Utils::getCurrentUrl().'"
                    data-redirect-cancel="'.Utils::getCurrentUrl().'"
                    data-collection-item="'.$this->event->id.'"
                    data-payment-meta="'.urlencode(json_encode(array("ticket-type" => "default"))).'"
                    data-price-field="price"
                    data-title="'.$this->event->getMeta('title').'"
                    data-api="'.Utils::getHomeUrl()."api/".'">Buy</a>';
        }
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
            ),
            array(
                'name' => ""
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