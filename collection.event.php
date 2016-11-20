<?php
class ForgeEventCollection extends DataCollection {
    public $permission = "manage.collection.sites";
    private $itemId = null;

    protected function setup() {
        $this->preferences['name'] = 'forge-events';
        $this->preferences['title'] = i('Events', 'forge-events');
        $this->preferences['all-title'] = i('Manage Events', 'forge-events');
        $this->preferences['add-label'] = i('Add event', 'forge-events');
        $this->preferences['single-item'] = i('Event', 'forge-events');

        $this->custom_fields();

        API::instance()->register('forge-events-tickets', array($this, 'ticketApiAdapter'));
    }

    public function ticketApiAdapter($args) {
        switch($args) {
            case 'clear-drafts':
                return $this->clearDrafts();
                break;
            case 'delete-order':
                return $this->deleteOrder();
                break;
            case 'accept-order':
                return $this->acceptOrder();
                break;
        }
    }

    private function acceptOrder() {
        $ticketTable = new TicketTable($_GET['event']);
        Payment::acceptOrder($_GET['order']);

        return json_encode([
            'action' => 'update',
            'target' => $ticketTable->tableId,
            'content' => $ticketTable->draw()
        ]);
    }

    private function deleteOrder() {
        $ticketTable = new TicketTable($_GET['event']);
        Payment::deleteOrder($_GET['order']);

        return json_encode([
            'action' => 'update',
            'target' => $ticketTable->tableId,
            'content' => $ticketTable->draw()
        ]);
    }

    private function clearDrafts() {
        $ticketTable = new TicketTable($_GET['event']);
        $ticketTable->removeDrafts();

        return json_encode([
            'action' => 'update',
            'target' => $ticketTable->tableId,
            'content' => $ticketTable->draw()
        ]);
    }

    public function render($item) {
    }

    public function customEditContent($id) {
        $this->itemId = $id;

        $return = '';
        $return.= $this->seatPlan();

        return $return;
    }

    public function getSubnavigation() {
        if(! Auth::allowed("manage.forge-events.ticket-status.view")) {
            return;
        }
        return [
            [
            'url' => 'tickets',
            'title' => i('Ticket Status')
            ]
        ];
    }

    public function subviewTickets($itemId) {
        if(! Auth::allowed("manage.forge-events.ticket-status.view")) {
            return;
        }
        $ticketTable = new TicketTable($itemId);
        return $ticketTable->draw();
    }

    public function subviewTicketsActions($itemId) {
        if(! Auth::allowed("manage.forge-events.ticket-status.edit")) {
            return;
        }
        $url = Utils::getUrl(
            ['api', 'forge-events-tickets', 'clear-drafts'],
            true,
            array('event' => $itemId)
        );
        return '<a class="ajax btn btn-xs" href="'.$url.'">'.i('Clear drafts', 'forge-events').'</a>';
    }


    public function userTicketAvailable($id, $user) {
        $db = App::instance()->db;
        $db->where("status", "success");
        $orders = $db->get("forge_payment_orders");
        foreach($orders as $order) {
            $orderMeta = json_decode(urldecode($order['meta']));
            foreach($orderMeta->{'items'} as $itemInOrder) {
                if(!is_numeric($user)) {
                    $user = User::exists($user);
                }
                if(!is_numeric($user)) {
                    return false;
                }
                if($itemInOrder->user == $user && $itemInOrder->collection == $id) {
                    return false;
                }
            }
        }
        return true;
    }

    private function seatPlan() {
        $sp = new Seatplan($this->itemId);
        return $sp->draw();
    }

    private function custom_fields() {
        $this->addFields(array(
            array(
                'key' => 'amount-of-participants',
                'label' => i('Number of participants', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
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
                'key' => 'start-date',
                'label' => i('Start Date', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
                'order' => 25,
                'position' => 'right',
                'hint' => ''
                ),
            array(
                'key' => 'start-time',
                'label' => i('Start Time', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
                'order' => 26,
                'position' => 'right',
                'hint' => ''
                ),
            array(
                'key' => 'end-date',
                'label' => i('End Date', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
                ),
            array(
                'key' => 'end-time',
                'label' => i('End Time', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
                ),
            array(
                'key' => 'price',
                'label' => i('Event Price', 'forge-events'),
                'multilang' => true,
                'type' => 'text',
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
                )
            ));
    }
}

?>
