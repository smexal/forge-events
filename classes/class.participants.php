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
        $parts = [];

        // TODO: make this unugly...
        $db->where('status', 'success');
        $db->where('meta', '%collection%22%3A'.$this->eventId.'%', 'LIKE');
        $parts = $db->get('forge_payment_orders');

        $db->where('status', 'success');
        $db->where('meta', '%collection%22%3A%22'.$this->eventId.'%', 'LIKE');
        $parts = array_merge($parts, $db->get('forge_payment_orders'));


        $rows = [];
        $sp = new Seatplan($itm->id);
        $counter = 0;
        foreach($parts as $part) {
            $meta = json_decode(urldecode($part['meta']));
            foreach($meta->items as $item) {
                $counter++;
                $seat = $sp->getUserSeat($item->user);
                $user = new User($item->user);

                $row = new \stdClass();
                $row->tds = $this->getParticipantTd($user, $seat);
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function getParticipantTd($user, $seat = null) {
        if($this->searchTerm) {
            $found = false;
            if(strstr(strtolower($user->get('username')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if(strstr(strtolower($user->get('email')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if(! is_null($seat)) {
                if(strstr(strtolower($seat), strtolower($this->searchTerm))) {
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
            if($seat === '') {
                $td[] = Utils::tableCell(i('No seat selected', 'forge-events'));
            } else {
                $td[] = Utils::tableCell($seat);
            }
        if($this->isAdmin) {
            if($seat === '') {
                $td[] = Utils::tableCell(i('No seat', 'forge-events'));
            } else {
                $td[] = Utils::tableCell($this->actions(Seatplan::getSeatId($seat, $this->eventId)));
            }
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
