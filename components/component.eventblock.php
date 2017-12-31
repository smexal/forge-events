<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Classes\Utils;
use \Forge\Core\Abstracts\Component;
use \Forge\Core\App\App;
use \Forge\Core\Classes\CollectionItem;

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
            ],
            [
                'label' => i('Primary CTA Title', 'forge-events'),
                'key' => $this->prefix.'prim_cta_title',
                'type' => 'text'
            ],
            [
                'label' => i('Primary CTA URL', 'forge-events'),
                'key' => $this->prefix.'prim_cta_url',
                'type' => 'url'
            ],
            [
                'label' => i('Secondary CTA Title', 'forge-events'),
                'key' => $this->prefix.'secondary_cta_title',
                'type' => 'text'
            ],
            [
                'label' => i('Secondary CTA URL', 'forge-events'),
                'key' => $this->prefix.'secondary_cta_url',
                'type' => 'url'
            ]
        ];
        return [
            'name' => i('Event Teaser Block'),
            'description' => i('Block with Event-Information.', 'forge-events'),
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

        $max = $collection->getEventMaximumAmount($eventId);
        $sold = $collection->getEventSoldAmount($eventId);
        $percent = 100 / $max * $sold;
        $remaining = $max - $sold;

        return App::instance()->render(DOC_ROOT.'modules/forge-events/templates/', "event-block", [
            'title' => $item->getMeta('title'),
            'blocktitle' => $this->getField($this->prefix.'title'),
            'lead' => $item->getMeta('description'),
            'text' => $item->getMeta('text'),
            'when_label' => i('When?', 'allocate'),
            'when_value' => Utils::dateFormat($item->getMeta('start-date'), true),
            'where_label' => i('Where?', 'allocate'),
            'where_value' => $item->getMeta('address'),
            'price_label' => i('How much?', 'allocate'),
            'price_value' => Utils::formatAmount($item->getMeta('price'), true),
            'prim_cta_title' => $this->getField($this->prefix.'prim_cta_title'),
            'prim_cta_url' => $this->getField($this->prefix.'prim_cta_url'),
            'secondary_cta_title' => $this->getField($this->prefix.'secondary_cta_title'),
            'secondary_cta_url' => $this->getField($this->prefix.'secondary_cta_url'),
            'progress_amount' => $percent,
            'remaining_seats' => sprintf(i('%1$s available tickets', 'forge-events'), $remaining), 
        ]);
    }
}


?>
