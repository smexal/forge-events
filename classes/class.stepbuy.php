<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\App\App;
use \Forge\Core\Classes\Fields;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;

use \Forge\Modules\ForgePayment\Payment;



class SignupStepBuy {
    public $id = 'signup-buy';
    private $event = false;

    public function __construct($event) {
        if (is_numeric($event)) {
            $collection = App::instance()->cm->getCollection('forge-events');
            $this->event = $collection->getItem($event);
        } else {
            $this->event = $event;
        }
    }

    public function content() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "buy", array(
            'title' => i('Your Tickets', 'forge-events'),
            'minimum_message' => $this->eventHasMinimum() ? $this->getMinimumText() : false,
            'table' => $this->getBuyTable(),
            'other_user_title' => i('Buy for another user', 'forge-events'),
            'other_user_desc' => i('Buy a ticket for another user, by typing a valid user`s E-Mail.', 'forge-events'),
            'other_user_url' => Utils::getUrl(array('api', 'forge-events', 'ticket-buy', $this->event->id, 'another-user')),
            'add_user_form' => $this->getUserAddInput()
        ));
    }

    public function eventHasMinimum() {
        $value = $this->event->getMeta('minimum_amount', 0);
        if(is_numeric($value) && $value > 1) {
            return true;
        }
        return;
    }

    public function getEventMinimum() {
        $value = $this->event->getMeta('minimum_amount', 0);
        if(is_numeric($value) && $value >= 1) {
            return $value;
        }
        return 0;
    }    

    public function getMinimumText() {
        $value = $this->event->getMeta('minimum_amount', 0);
        return sprintf(i('The minimum ticket amount to buy is currently <strong>%1$s.</strong>', 'forge-events'), $value);
    }

    public function getBuyTable() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/parts/", "buy-table", array(
            'buytableurl' => Utils::getUrl(array(
                "api", "forge-events", "ticket-buy", $this->event->id, "buy-table"
            )),
            'tr' => $this->getTr(),
            'td' => $this->getTds(),
            'tb' => $this->getTb()
        ));
    }

    private function getTb() {
        if (! array_key_exists('savedUsers', $_SESSION)) {
            return false;
        }
        $users = $_SESSION['savedUsers'];
        array_push($users, App::instance()->user->get('id'));

        return array(
            'colspan' => 4,
            'label' => i('Total amount'),
            'total' => $this->getTotalAmount($users),
            'bbuyall' => $this->getAction($users)
        );
    }

    public function getTotalAmount($users) {
        $price = 0;
        $collection = $this->event->getCollection();
        foreach ($users as $user) {
            if ($collection->userTicketAvailable($this->event->id, $user)) {
                $price += $this->event->getMeta('price');
            }
        }
        if ($price == 0) {
            return '-';
        } else {
            return Utils::formatAmount($price);
        }
    }

    public function addAnotherUser($usermail = false) {
        if (!Utils::isEmail($usermail)) {
            return array(
                "errors" => array(
                    array(
                        'message' => i('Invalid E-Mail Address.', 'forge-events'),
                        'field' => 'buy-for-another-user'
                    )
                )
            );
        }
        $userid = User::exists($usermail);
        if ($userid == App::instance()->user->get('id')) {
            return array();
        }

        if ($userid) {
            $user = new User($userid);
            if ($user->get('active') == 0) {
                return array(
                    "errors" => array(
                        array(
                            'message' => i('The user you are looking for has not activated its account.', 'forge-events'),
                            'field' => 'buy-for-another-user'
                        )
                    )
                );
            } else {
                // we're all set.
                $this->saveBuyOption($usermail);
                return array("action" => "reload-specific", "target" => "#ticket-buy-table");
            }
        } else {
            return array(
                "errors" => array(
                    array(
                        'message' => i('There is no user, with this given E-Mail Address.', 'forge-events'),
                        'field' => 'buy-for-another-user'
                    )
                )
            );
        }
        return array();
    }

    private function saveBuyOption($email) {
        if (array_key_exists('savedUsers', $_SESSION) && !is_null($_SESSION['savedUsers'])) {
            $users = $_SESSION['savedUsers'];
        } else {
            $users = array();
        }
        if (!in_array($email, $users)) {
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

    private function getTicketStatus($userid) {
        $collection = $this->event->getCollection();
        if ($collection->userTicketAvailable($this->event->id, $userid)) {
            return '<span class="special">'.i('Available', 'forge-events').'</span>';
        } else {
            $buyer = $collection->getTicketBuyer($this->event->id, $userid);
            if(App::instance()->user->get('id') == $buyer->get('id')) {
                return '<span class="discreet">'.i('Purchased', 'forge-events').'</span>';
            } else {
                return '<span class="discreet">'.sprintf(i('Purchased (by %1$s)', 'forge-events'), $buyer->get('username')).'</span>';
            }
        }
    }

    public function getTds() {
        if (is_null(App::instance()->user)) {
            return '';
        }
        $rows = array();

        // add current user
        array_push($rows, $this->getTd(App::instance()->user));
        $users = [];

        if (array_key_exists('savedUsers', $_SESSION) && is_array($_SESSION['savedUsers'])) {
            foreach ($_SESSION['savedUsers'] as $otherUser) {
                $userid = User::exists($otherUser);
                $user = new User($userid);
                array_push($rows, $this->getTd($user));
                $users[] = $userid;
            }
        }

        $orders = Payment::getPayments(App::instance()->user->get('id'));
        foreach($orders as $order) {
            foreach($order['meta']->items as $item) {
                if($item->collection == $this->event->id) {
                    if($item->user == App::instance()->user->get('id')) {
                        continue;
                    }
                    if(in_array($item->user, $users)) {
                        continue;
                    }
                    $userid = User::exists($item->user);
                    $user = new User($item->user);
                    array_push($rows, $this->getTd($user));
                    $users[] = $userid;
                }
            }
        }

        return $rows;
    }

    private function amountOfBuyedTickets() {
        $buyed = 0;
        $orders = Payment::getPayments(App::instance()->user->get('id'));
        foreach($orders as $order) {
            foreach($order['meta']->items as $item) {
                if($item->collection == $this->event->id) {
                    $buyed++;
                }
            }
        }
        return $buyed;
    }

    private function getTd($user) {
        return array(
            $this->getTicketStatus($user->get('id')),
            $user->get('username').' ('.$user->get('email').')',
            i('default', 'forge-events'),
            Utils::formatAmount($this->event->getMeta('price')),
            $this->getAction($user->get('id'))
        );
    }

    public function getAction($users) {
        $collection = $this->event->getCollection();
        $ticketUser = false;
        if (is_array($users)) {
            // buy ticket for multiple users...
            if(count($users) < $this->getEventMinimum() && $this->amountOfBuyedTickets() < $this->getEventMinimum()) {
                return;
            }
            foreach ($users as $user) {
                if ($collection->userTicketAvailable($this->event->id, $user)) {
                    if (! is_array($ticketUser)) {
                        $ticketUser = array();
                    }
                    array_push($ticketUser, User::exists($user));
                }
            }
            if (is_array($ticketUser)) {
                $label = i('Buy all', 'forge-events');
            }
        } else {
            if($this->getEventMinimum() > 1 && $this->amountOfBuyedTickets() < $this->getEventMinimum()) {
                return;
            }
            // buy only one ticket
            if ($collection->userTicketAvailable($this->event->id, $users)) {
                $ticketUser = array($users);
                $label = i('Buy', 'forge-events');
            }
        }

        if ($ticketUser) {
            $items = array();
            foreach ($ticketUser as $user) {
                array_push($items, array(
                    'collection' => $this->event->id,
                    'user' => $user,
                    'amount' => 1
                ));
            }
            return Payment::button(array(
                "items" => $items,
                "title" => $this->event->getMeta('title'),
                "label" => $label,
                "success" => Utils::getUrl(['event-signup', $this->event->slug()]),
                "cancel" => Utils::getUrl(['event-signup', $this->event->slug()])
            ));
        } else {
            return false;
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
        if (is_null(App::instance()->user)) {
            return false;
        }
        if (App::instance()->user->get('active') == 0) {
            return false;
        }
        return true;
    }


}

?>
