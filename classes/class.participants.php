<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Classes\Utils;
use \Forge\Core\App\App;

class Participants {

    public function __construct() {

    }

    public function renderTable() {
        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "table", array(
            'id' => 'participantsTable',
            'th' => $this->getThs(),
            'td' => $this->getParticipants()
        ));
    }

    public function getThs() {
        $ths = [];
        $ths[] = Utils::tableCell(i('ID', 'forge-events'));
        $ths[] = Utils::tableCell(i('User', 'forge-events'));
        $ths[] = Utils::tableCell(i('Seat', 'forge-events'));
        return $ths;
    }

    public function getParticipants() {
        return [];
    }

}