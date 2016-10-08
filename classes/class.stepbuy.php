<?php

class SignupStepBuy {
    public $id = 'signup-buy';
    private $event = false;

    public function __construct($event) {
        if(is_numeric($event)) {
            $collection = App::instance()->cm->getCollection('forge-events');
            $this->event = $collection->getItem($event);
        } else {
            $this->event = $event;
        }
    }

    public function content() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "buy", array(
            'title' => i('Your Tickets', 'forge-events'),
            'table' => $this->getBuyTable(),
            'other_user_title' => i('Buy for another user'),
            'other_user_desc' => i('Buy a ticket for another user, by typing a valid user\'s E-Mail.'),
            'other_user_url' => Utils::getUrl(array('api', 'forge-events', 'ticket-buy', $this->event->id, 'another-user')),
            'add_user_form' => $this->getUserAddInput()
        ));
    }

    public function getBuyTable() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/parts/", "buy-table", array(
            'buytableurl' => Utils::getUrl(array(
                "api", "forge-events", "ticket-buy", $this->event->id, "buy-table"
            )),
            'tr' => $this->getTr(),
            'td' => $this->getTds()
        ));
    }

    public function addAnotherUser($usermail = false) {
        if(!Utils::isEmail($usermail)) {
            return array("error" => i('Invalid E-mail Address', 'forge-events'));
        }
        $userid = User::exists($usermail);
        if($userid == App::instance()->user->get('id')) {
            return array();
        }

        if($userid) {
            $user = new User($userid);
            if($user->get('active') == 0) {
                return array("error" => i('The user you are looking for has not activated its account.'));
            } else {
                // we're all set.
                $this->saveBuyOption($usermail);
                return array("action" => "reload-specific", "target" => "#ticket-buy-table");
            }
        } else {
            return array("error" => i('There is no user with this E-Mail Address', 'forge-events'));
        }
        return array();
    }

    private function saveBuyOption($email) {
        if(array_key_exists('savedUsers', $_SESSION) && !is_null($_SESSION['savedUsers'])) {
            $users = $_SESSION['savedUsers'];
        } else {
            $users = array();
        }
        if(!in_array($email, $users)) {
            array_push($users, $email);
        }
        $_SESSION['savedUsers'] = $users;
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

    public function getTds() {
        if(is_null(App::instance()->user)) {
            return '';
        }
        $rows = array();

        // add current user
        array_push($rows, $this->getTd(App::instance()->user));

        if(array_key_exists('savedUsers', $_SESSION) && is_array($_SESSION['savedUsers'])) {
            foreach($_SESSION['savedUsers'] as $otherUser) {
                $userid = User::exists($otherUser);
                $user = new User($userid);
                array_push($rows, $this->getTd($user));
            }
        }


        return $rows;
    }

    private function getTd($user) {
        return array(
            $this->getTicketStatus($user->get('id')), 
            $user->get('username').' ('.$user->get('email').')', 
            'default', 
            Utils::formatAmount($this->event->getMeta('price')),
            $this->getAction($user->get('id'))
        );
    }

    public function getAction($userid) {
        $collection = $this->event->getCollection();
        if($collection->userTicketAvailable($this->event->id, $userid)) {
            return '<a href="#" class="btn btn-discreet payment-trigger" 
                    data-redirect-success="'.Utils::getCurrentUrl().'"
                    data-redirect-cancel="'.Utils::getCurrentUrl().'"
                    data-collection-item="'.$this->event->id.'"
                    data-payment-meta="'.urlencode(json_encode(array(
                        "ticket-user" => $userid
                    ))).'"
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