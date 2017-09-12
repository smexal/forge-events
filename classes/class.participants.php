<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\App\App;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\TableBar;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;

class Participants {
    /*
    ALTER TABLE `forge_events_seat_reservations` ADD `checkin` INT NOT NULL DEFAULT '0' AFTER `locked`;
     */
    private $eventId = false;
    private $searchTerm = false;
    public $isAdmin = false;

    public function __construct($eventId) {
        $this->eventId = $eventId;
    }

    public function renderTable() {
        $bar = new TableBar(Utils::url(['api', 'forge-events', 'participants', $this->eventId]), 'participantsTable');
        $bar->enableSearch();

        return $bar->render().App::instance()->render(CORE_TEMPLATE_DIR."assets/", "table", array(
            'id' => 'participantsTable',
            'th' => $this->getThs(),
            'td' => $this->getParticipants()
        ));
    }

    public function handleQuery($action) {
        switch($action) {
            case 'search':
                $this->searchTerm = $_GET['t'];
                return json_encode([
                    'newTable' => App::instance()->render(
                        CORE_TEMPLATE_DIR.'assets/', 
                        'table-rows', 
                        ['td' => $this->getParticipants()]
                    )
                ]);
            default:
                break;
        }
    }

    public function getThs() {
        $ths = [];
        $ths[] = Utils::tableCell('');
        $ths[] = Utils::tableCell(i('Username', 'forge-events'));
        if($this->isAdmin) {
            $ths[] = Utils::tableCell(i('E-Mail', 'forge-events'));
        }
        $colItem = new CollectionItem($this->eventId);
        if( $colItem->getMeta('disable-seatplan') !== 'on') {
            $ths[] = Utils::tableCell(i('Seat', 'forge-events'));
        }
        if($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Actions', 'forge-events'));
        }
        return $ths;
    }

    public function getParticipants() {
        $itm = new CollectionItem($this->eventId);
        $db = App::instance()->db;
        $withSeatplan = true;
        if($itm->getMeta('disable-seatplan')) {
            $withSeatplan = false;
            $parts = [];
            //collection%22%3A11
            $db->where('meta', '%collection%22%3A'.$itm->id.'%', 'LIKE');
            $parts = $db->get('forge_payment_orders');
        } else {
            $db->where('event_id', $this->eventId);
            $db->orderBy("x","asc");
            $db->orderBy("y","asc");

            $parts = $db->get('forge_events_seat_reservations');
        }
        $tds = [];
        foreach($parts as $part) {
            $row = new \stdClass();
            if(! $withSeatplan) {
                $meta = json_decode(urldecode($part['meta']));
                foreach($meta->items as $item) {
                    $user = new User($item->user);
                    $row->tds = $this->getParticipantTd($user);
                }
            } else {
                $user = new User($part['user']);
                $row->tds = $this->getParticipantTd($user, $part);
            }
            array_push($tds, $row);
        }
        return $tds;
    }

    private function getParticipantTd($user, $part = null) {
        if($this->searchTerm) {
            $found = false;
            if(strstr(strtolower($user->get('username')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if(strstr(strtolower($user->get('email')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if(! is_null($part)) {
                if(strstr(strtolower($part['x'].':'.$part['y']), strtolower($this->searchTerm))) {
                    $found = true;
                }
            }
            if(! $found) {
                return;
            }
        }
        $td = [];
        if(! is_null($user->getAvatar())) {
            $avatar = '<img src="'.$user->getAvatar().'" style="border-radius: 15px; width: 30px; max-height:30px; margin-left: 10px; "/>';
        } else {
            $avatar = '';
        }
        $td[] = Utils::tableCell($avatar);
        $td[] = Utils::tableCell($user->get('username'));
        if($this->isAdmin) {
            $td[] = Utils::tableCell($user->get('email'));
        }
        if(! is_null($part)) {
            $td[] = Utils::tableCell($part['x'].':'.$part['y']);
        }
        if($this->isAdmin) {
            $td[] = Utils::tableCell($this->actions($part['id']));
        }
        return $td;
    }

    private function actions($id) {
        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "table.actions", array(
            'actions' => array(
                array(
                    "url" => Utils::getUrl(Utils::getUriComponents(), true, ['deleteSeat' => $id]),
                    "icon" => "delete",
                    "name" => i('Delete Seat Reservation', 'forge-events'),
                    "ajax" => true,
                    "confirm" => false
                )
            )
        ));
    }

    public function delete($id) {
        $db = App::instance()->db;
        $db->where('event_id', $this->eventId);
        $db->where('id', $id);
        $db->delete('forge_events_seat_reservations');
    }

}