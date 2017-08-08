<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Abstracts\Component;
use \Forge\Core\App\App;

class EventprogressComponent extends Component {
    public $settings = [];
    private $prefix = 'forge_events_progress_';

    public function prefs() {
        $this->settings = [
            [
                'label' => i('Choose an event', 'forge-events'),
                'hint' => '',
                'key' => $this->prefix.'event',
                'type' => 'select',
                'chosen' => true,
                'callable' => true,
                'values' => [$this, 'getEvenSelection']
            ]
        ];
        return [
            'name' => i('Event Progress Bar'),
            'description' => i('Add a Progress Bar for an event.', 'forge-events'),
            'id' => 'forge_events_progress',
            'image' => '',
            'level' => 'inner',
            'container' => false
        ];
    }

    public function getEvenSelection() {
        $collection = App::instance()->cm->getCollection('forge-events');
        $items = $collection->items([
            'order' => 'created',
            'order_direction' => 'desc',
            'status' => 'published'
        ]);
        $list = [];
        foreach ($items as $item) {
            $list[$item->id] = $item->getName();
        }

        return ['0' => i('Choose one', 'forge-events')] + $list;
    }

    public function content() {
        $eventId = $this->getField($this->prefix.'event');

        $collection = App::instance()->cm->getCollection('forge-events');
        $totalAmount = $collection->getEventMaximumAmount($eventId);
        $takenAmount = $collection->getEventSoldAmount($eventId);

        $percent = 100 / $totalAmount * $takenAmount;
        $remaining = $totalAmount - $takenAmount;

        return App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'progressbar', [
            'id' => 'event_progress_bar',
            'current' => $percent,
            'min' => 0,
            'max' => $totalAmount,
            'text' => sprintf(i('%1$s remaining seats.', 'forge-events'), $remaining)
        ]);
    }
}


?>
