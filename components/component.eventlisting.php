<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\App\App;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Components\ListingComponent;
use Forge\Core\Classes\Media;


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
            ],
            [
                "label" => i('Display Image', 'forge-events'),
                "hint" => i('Display event image as background.', 'forge-events'),
                'key' => 'display_image',
                'type' => 'checkbox',
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
        return $items;
    }

    public function arraySort( $a, $b ) {
        if(! $a->getMeta('start-date')) {
            return -1;
        }
        $ta = $a->getMeta('start-date');
        $tb = $b->getMeta('start-date');
        if(is_numeric($ta)) {
            $ta = "01.01.".$ta;
        }
        if(is_numeric($tb)) {
            $tb = "01.01.".$tb;
        }
        $tA = new \DateTime($ta);
        $tB = new \DateTime($tb);
        $cmpA = $tA->getTimestamp();
        $cmpB = $tB->getTimestamp();

        if(is_string($cmpA)) {
            return 1;
        }

        if( $cmpA == $cmpB ) {
            return 0;
        }
        return ($cmpA < $cmpB) ? -1 : 1;
    }

    public function renderItem($item) {
        if( $this->getField('display_image') === 'on' && $imgId = $item->getMeta('collection_image') ) {
            $image = new Media($imgId);
        } else {
            $image = false;
        }
        $classes = '';
        $startdate = new \DateTime($item->getMeta('start-date'));
        $datenow = new \DateTime();
        if($datenow < $startdate) {
            $classes.= 'upcoming';
        } else {
            $classes.= 'past';
        }
        return App::instance()->render(MOD_ROOT.'forge-events/templates/', 'listing-item', array(
            'classes' => $classes,
            'title' => $item->getMeta('title'),
            'description' => $item->getMeta('description'),
            'start_date' => $item->getMeta('start-date'),
            'end_date' => $item->getMeta('end-date'),
            'opening_times' => strlen($item->getMeta('opening-times')) ? i('Opening Times: ', 'forge-events').$item->getMeta('opening-times') : '',
            'image' => $image ? $image->getUrl() : false,
            'text' => $item->getMeta('text'),
            'url' => $item->getMeta('hide-detail') == 'on' ? false : $item->url()
        ));
    }
}
?>
