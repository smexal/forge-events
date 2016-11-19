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
    return [
        [
            'url' => 'tickets',
            'title' => i('Ticket Status')
        ]
    ];
  }

  public function subviewTickets($itemId) {
    $ticketTable = new TicketTable($itemId);
    return $ticketTable->draw();
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
