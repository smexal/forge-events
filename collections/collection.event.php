<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Classes\Settings;
use \Forge\Core\Abstracts\DataCollection;
use \Forge\Core\App\App;
use \Forge\Core\App\Auth;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;



class EventCollection extends DataCollection {
    public $permission = "manage.collection.sites";
    private $itemId = null;

    protected function setup() {
        $this->preferences['name'] = 'forge-events';
        $this->preferences['title'] = i('Events', 'forge-events');
        $this->preferences['all-title'] = i('Manage Events', 'forge-events');
        $this->preferences['add-label'] = i('Add event', 'forge-events');
        $this->preferences['single-item'] = i('Event', 'forge-events');

        $this->custom_fields();
    }

    public function render($item) {
        if($item->getMeta('hide-detail')) {
            App::instance()->redirect('404');
        }
        return App::instance()->render(MOD_ROOT.'forge-events/templates/', 'event-detail', [
            'title' => $item->getMeta('title'),
            'lead' => $item->getMeta('description'),
            'text' => $item->getMeta('text'),
            'start_date' => $item->getMeta('start-date'),
            'end_date' => $item->getMeta('end-date'),
            'address' => $item->getMeta('address'),
            'signup' => $item->getMeta('allow-signup'),
            'signup_text' => i('Signup for this event', 'forge-events'),
            'signup_url' => Utils::url(['event-signup', $item->slug()])
        ]);
    }

    public function customEditContent($id) {
        $this->itemId = $id;

        $return = '';
        if(Settings::get('forge-events-seatplan')) {
            $return.= $this->seatPlan();
        }

        return $return;
    }

    public function getSubnavigation() {
        return [
            [
            'url' => 'participants',
            'title' => i('Participants', 'forge-events')
            ]
        ];

    }

    public function subviewParticipants($itemId) {
        if(! Auth::allowed("manage.forge-events")) {
            return;
        }

        $participants = new Participants($itemId);

        if(array_key_exists('deleteSeat', $_GET) && is_numeric($_GET['deleteSeat'])) {
            $participants->delete($_GET['deleteSeat']);
        }
        return $participants->renderTable();
    }

    public function subviewParticipantsActions($itemId) {
        return '';
    }

    public function userTicketAvailable($id, $user) {
        $db = App::instance()->db;
        $db->where("status", "success");
        $orders = $db->get("forge_payment_orders");
        foreach ($orders as $order) {
            $orderMeta = json_decode(urldecode($order['meta']));
            foreach ($orderMeta->{'items'} as $itemInOrder) {
                if (!is_numeric($user)) {
                    $user = User::exists($user);
                }
                if (!is_numeric($user)) {
                    return false;
                }
                if ($itemInOrder->user == $user && $itemInOrder->collection == $id) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getTicketBuyer($id, $user) {
        $db = App::instance()->db;
        $db->where("status", "success");
        $orders = $db->get("forge_payment_orders");
        foreach ($orders as $order) {
            $orderMeta = json_decode(urldecode($order['meta']));
            foreach ($orderMeta->{'items'} as $itemInOrder) {
                if ($itemInOrder->user == $user && $itemInOrder->collection == $id) {
                    return new User($order['user']);
                }
            }
        }
    }

    public function getTicketOrder($id, $user) {
        $db = App::instance()->db;
        $db->where("status", "success");
        $orders = $db->get("forge_payment_orders");
        foreach ($orders as $order) {
            $orderMeta = json_decode(urldecode($order['meta']));
            foreach ($orderMeta->{'items'} as $itemInOrder) {
                if ($itemInOrder->user == $user && $itemInOrder->collection == $id) {
                    return $order['user'];
                }
            }
        }
    }

    public function getEventMaximumAmount($eventId) {
        $sp = new Seatplan($eventId);
        return $sp->getSeatAmount();
    }

    public function getEventSoldAmount($eventId) {
        $sp = new Seatplan($eventId);
        return $sp->getSoldAmount();
    }

    private function seatPlan() {
        $sp = new Seatplan($this->itemId);
        return $sp->draw();
    }

    private function custom_fields() {
        $this->addFields(
            array_merge(
                [
                    array(
                        'key' => 'amount-of-participants',
                        'label' => i('Number of participants', 'forge-events'),
                        'multilang' => true,
                        'type' => 'number',
                        'order' => 20,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'allow-signup',
                        'label' => i('Allow Event Signups', 'forge-events'),
                        'multilang' => true,
                        'type' => 'checkbox',
                        'order' => 20,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'hide-detail',
                        'label' => i('Hide Detail', 'forge-events'),
                        'multilang' => true,
                        'type' => 'checkbox',
                        'order' => 40,
                        'position' => 'right',
                        'hint' => i('If this checkbox is active, people will not be able to check the detail page.', 'forge-events')
                    ),
                    array(
                        'key' => 'start-date',
                        'label' => i('Start Date', 'forge-events'),
                        'multilang' => true,
                        'type' => 'datetime',
                        'order' => 25,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'end-date',
                        'label' => i('End Date', 'forge-events'),
                        'multilang' => true,
                        'type' => 'datetime',
                        'order' => 30,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'price',
                        'label' => i('Event Price', 'forge-events'),
                        'multilang' => true,
                        'type' => 'number',
                        'order' => 19,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'address',
                        'label' => i('Address', 'forge-events'),
                        'multilang' => true,
                        'type' => 'text',
                        'order' => 18,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'text',
                        'label' => i('Text', 'forge-events'),
                        'multilang' => true,
                        'type' => 'wysiwyg',
                        'order' => 10,
                        'position' => 'left',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'minimum_amount',
                        'label' => i('Minimum Amount of Tickets per Buy', 'forge-events'),
                        'multilang' => false,
                        'type' => 'number',
                        'order' => 20,
                        'position' => 'right',
                        'hint' => ''
                    ),
                    array(
                        'key' => 'header_image',
                        'label' => i('Header Image', 'forge-events'),
                        'multilang' => false,
                        'type' => 'image',
                        'order' => 40,
                        'position' => 'right',
                        'hint' => ''
                    )
                ],
                $this->seatPlanRows()
            )
        );
    }

    private function seatPlanRows() {
        if(! Settings::get('forge-events-seatplan'))
            return [];

        return [
            [
                'key' => 'seatplan_rows',
                'label' => i('Amount of Rows on the Seatplan', 'forge-events'),
                'multilang' => false,
                'type' => 'number',
                'order' => 20,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'seatplan_columns',
                'label' => i('Amount of Columns on the Seatplan', 'forge-events'),
                'multilang' => false,
                'type' => 'number',
                'order' => 21,
                'position' => 'right',
                'hint' => ''
            ],
        ];
    }
}

?>
