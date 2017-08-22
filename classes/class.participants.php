<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Classes\Utils;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\TableBar;
use \Forge\Core\App\App;

class Participants {
    /*
    ALTER TABLE `forge_events_seat_reservations` ADD `checkin` INT NOT NULL DEFAULT '0' AFTER `locked`;
     */
    private $eventId = false;
    private $searchTerm = false;

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
        $ths[] = Utils::tableCell(i('Seat ID', 'forge-events'));
        $ths[] = Utils::tableCell(i('Username', 'forge-events'));
        $ths[] = Utils::tableCell(i('E-Mail', 'forge-events'));
        $ths[] = Utils::tableCell(i('Seat', 'forge-events'));
        $ths[] = Utils::tableCell(i('Actions', 'forge-events'));
        return $ths;
    }

    public function getParticipants() {
        $db = App::instance()->db;
        $db->where('event_id', $this->eventId);
        $db->orderBy("x","asc");
        $db->orderBy("y","asc");

        $parts = $db->get('forge_events_seat_reservations');
        $tds = [];
        foreach($parts as $part) {
            $user = new User($part['user']);
            if($this->searchTerm) {
                $found = false;
                if(strstr($user->get('username'), $this->searchTerm)) {
                    $found = true;
                }
                if(strstr($user->get('email'), $this->searchTerm)) {
                    $found = true;
                }
                if(strstr($part['x'].':'.$part['y'], $this->searchTerm)) {
                    $found = true;
                }
                if(! $found) {
                    continue;
                }
            }
            $td = [];
            $td[] = Utils::tableCell($part['id']);
            $td[] = Utils::tableCell($user->get('username'));
            $td[] = Utils::tableCell($user->get('email'));
            $td[] = Utils::tableCell($part['x'].':'.$part['y']);
            $td[] = Utils::tableCell($this->actions($part['id']));
            $tds[] = $td;
        }
        return $tds;
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