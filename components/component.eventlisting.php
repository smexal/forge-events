<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\App\App;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Components\ListingComponent;


class EventlistingComponent extends ListingComponent {
    protected $collection = 'forge-events';
    protected $order = 'created';
    protected $oderDirection = 'DESC';

    public function prefs() {
        ModifyHandler::instance()->add('modify_collection_listing_items', [$this, 'modifyCollectionListingOrder']);

        $this->settings = [
            [
                "label" => i('Title', 'forge-events'),
                "hint" => 'Title, which will be displayed on top of the listing.',
                'key' => 'title',
                'type' => 'text',
            ]
        ];
        return array(
            'name' => i('Event Listing', 'forge-events'),
            'description' => i('Listing for the event collections.', 'forge-events'),
            'id' => 'fe_listing',
            'image' => '',
            'level' => 'inner',
            'container' => false
        );
    }

    public function modifyCollectionListingOrder($items) {
        usort($items, [$this, 'arraySort']);
        return array_reverse($items);
    }

    public function arraySort( $a, $b ) {
        if(! $a->getMeta('start-date') || ! $b->getMeta('start-date')) {
            return -1;
        }
        $tA = new \DateTime($a->getMeta('start-date'));
        $tB = new \DateTime($b->getMeta('start-date'));
        $cmpA = $tA->getTimestamp();
        $cmpB = $tB->getTimestamp();

        if(is_string($cmpA)) {
            return -1;
        }

        if( $cmpA == $cmpB ) {
            return 0;
        }
        return ($cmpA < $cmpB) ? -1 : 1;
    }

    public function renderItem($item) {
        return App::instance()->render(MOD_ROOT.'forge-events/templates/', 'listing-item', array(
            'title' => $item->getMeta('title'),
            'description' => $item->getMeta('description'),
            'start_date' => $item->getMeta('start-date'),
            'end_date' => $item->getMeta('end-date'),
            'text' => $item->getMeta('text'),
            'url' => $item->url()
        ));
    }
}
?>
