<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Classes\Settings;
use \Forge\Core\Abstracts\DataCollection;
use \Forge\Core\App\App;
use \Forge\Core\App\Auth;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;
use \Forge\Core\Classes\Media;
use \Forge\Core\Classes\CollectionItem;



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
        $header_image = false;
        if($header_image = $item->getMeta('header_image')) {
            $header_image = new Media($header_image);
        }

        $participants = new Participants($item->id);
        if(Auth::allowed('manage.forge-events', true)) {
            $participants->isAdmin = true;
        }
        $participantsTable = $participants->renderTable();
        if($item->getMeta('disable-seatplan') == 'on') {
            $seatplan = false;
        } else {
            $seatplan = new Seatplan($item->id, true);
            $seatplan->actions = false;
        }

        $ticketsAvailable = $this->getEventMaximumAmount($item->id) > $this->getEventSoldAmount($item->id);

        $buttonText = i('Signup now', 'forge-events');
        if(! $ticketsAvailable) {
            $buttonText = i('Sold out', 'forge-events');
        }

        return App::instance()->render(MOD_ROOT.'forge-events/templates/', 'event-detail', [
            'header_image' => $header_image ? $header_image->getUrl() : false,
            'title' => $item->getMeta('title'),
            'lead' => $item->getMeta('description'),
            'text' => $item->getMeta('text'),
            'start_date_label' => i('Starting date', 'forge-events'),
            'start_date' => $item->getMeta('start-date'),
            'location_label' => i('Location', 'forge-events'),
            'location' => $item->getMeta('address'),
            'price_label' => i('Price', 'forge-events'),
            'price' => Utils::formatAmount($item->getMeta('price'), true),
            'participants_amount_label' => i('Amount of participants', 'forge-events'),
            'participants_amount' => $this->getEventMaximumAmount($item->id),
            'end_date' => $item->getMeta('end-date'),
            'address' => $item->getMeta('address'),
            'signup' => $item->getMeta('allow-signup'),
            'signup_text' => $buttonText,
            'signup_url' => $ticketsAvailable ? Utils::url(['event-signup', $item->slug()]) : '#',
            'location_info_label' => i('Location', 'forge-events'),
            'location_info' => $item->getMeta('location-info'),
            'participantsTable' => $participantsTable,
            'participants_label' => i('Participants', 'forge-events'),
            'seatplan_label' => i('Seatplan', 'forge-events'),
            'seatplan' => $seatplan ? $seatplan->draw() : false,
            'additional' => $item->getMeta('additional-info'),
            'additional_label' => i('Additional Information', 'forge-events')
        ]);
    }

    public function customEditContent($id) {
        $this->itemId = $id;
        $colItem = new CollectionItem($id);

        $return = '';
        if(Settings::get('forge-events-seatplan') && $colItem->getMeta('disable-seatplan') != 'on') {
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
        if(Auth::allowed('manage.forge-events', true)) {
            $participants->isAdmin = true;
        }

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
        $colItem = new CollectionItem($eventId);
        if($colItem->getMeta('disable-seatplan') == 'on') {
            return $colItem->getMeta('amount-of-participants');
        } else {
            $sp = new Seatplan($eventId);
            return $sp->getSeatAmount();
        }
    }

    public function getEventSoldAmount($eventId) {
        $colItem = new CollectionItem($eventId);
        if($colItem->getMeta('disable-seatplan') == 'on') {
            return $this->getSoldAmountByPayments($eventId);
        } else {
            $sp = new Seatplan($eventId);
            return $sp->getSoldAmount();
        }
    }

    public function getSoldAmountByPayments($itemId) {
        $amt = 0;
        $db = App::instance()->db;
        $db->where('meta', '%collection%22%3A'.$itemId.'%', 'LIKE');
        $db->where('status', 'success');
        $parts = $db->get('forge_payment_orders');
        foreach($parts as $part) {
            $meta = json_decode(urldecode($part['meta']));
            foreach($meta->items as $item) {
                $amt++;
            }
        }
        return $amt;
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
                        'multilang' => false,
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
                    ),
                    array (
                        'key' => 'location-info',
                        'label' => i('Location Informations', 'forge-events'),
                        'type' => 'wysiwyg',
                        'order' => 80,
                        'position' => 'left'
                    ),
                    array (
                        'key' => 'additional-info',
                        'label' => i('Additional Informations', 'forge-events'),
                        'type' => 'wysiwyg',
                        'order' => 90,
                        'position' => 'left'
                    ),
                    array (
                        'key' => 'disable-seatplan',
                        'label' => i('Disable Seatplan Management', 'forge-events'),
                        'type' => 'checkbox',
                        'order' => 80,
                        'position' => 'right'
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
