<?php
class ForgeEventCollection extends DataCollection {
  public $permission = "manage.collection.sites";

  private $item_id = null;

  protected function setup() {
    $this->preferences['name'] = 'forge-events';
    $this->preferences['title'] = i('Events', 'forge-events');
    $this->preferences['all-title'] = i('Manage Events', 'forge-events');
    $this->preferences['add-label'] = i('Add event', 'forge-events');
    $this->preferences['single-item'] = i('Collection', 'forge-events');

    $this->custom_fields();
  }

  public function render($item) {
  }

  public function customEditContent($id) {
    $this->item_id = $id;

    $return = '';
    $return.= $this->seatPlan();

    return $return;
  }

  private function seatPlan() {
    $sp = new Seatplan($this->item_id);
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
