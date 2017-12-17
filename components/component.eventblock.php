<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Classes\CollectionItem;
use \Forge\Core\Abstracts\Component;
use \Forge\Core\App\App;

class EventblockComponent extends Component {
    public $settings = [];
    private $prefix = 'feb_';

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
            ],
            [
                'label' => i('Title', 'forge-events'),
                'hint' => i('Title the block', 'forge-events'),
                'key' => $this->prefix.'title',
                'type' => 'text'
            ]
        ];
        return [
            'name' => i('Event Teaser Block'),
            'description' => i('Event Teaser Block with some Event-Information.', 'forge-events'),
            'id' => 'forge_events_eblock',
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
        $item = new CollectionItem($eventId);

        return App::instance()->render(DOC_ROOT.'modules/forge-events/templates/', "event-block", [
            'title' => $item->getMeta('title'),
            'blocktitle' => $this->getField($this->prefix.'title')
        ]);
    }
}


?>
