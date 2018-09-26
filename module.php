<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\Module;
use Forge\Core\App\API;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Logger;
use Forge\Core\Classes\Settings;
use Forge\Core\Classes\Utils;
use Forge\Loader;
use Forge\Modules\ForgePayment\Payment;


class ForgeEvents extends Module {
    private $permission = 'manage.forge-events';
    private $permission_checkin = 'manage.checkin';

    public function setup() {
        $this->version = '0.1.0';
        $this->id = "forge-events";
        $this->name = i('Event Management', 'forge-events');
        $this->description = i('Event Management for Forge.', 'forge-events');
        $this->image = $this->url() . 'assets/images/module-image.png';
    }

    public function start() {
        $this->install();
        $this->registerSettings();


        if (App::instance()->mm->isActive('forge-payment')) {
            ModifyHandler::instance()->add(
                'modify_order_table_th',
                [$this, 'orderTableHeading']
            );
            ModifyHandler::instance()->add(
                'modify_order_table_td',
                [$this, 'orderTableRow']
            );
            ModifyHandler::instance()->add(
                'update_item_filter_order_table',
                [$this, 'itemFilterOrderTable']
            );
        }

        Auth::registerPermissions($this->permission);
        Auth::registerPermissions("manage.forge-events.ticket-status.view");
        Auth::registerPermissions("manage.forge-events.ticket-status.edit");
        Auth::registerPermissions($this->permission_checkin);

        if (Settings::get('forge-events-seatplan')) {
            ModifyHandler::instance()->add(
                'core_delete_user',
                function ($user) {
                    // delete user seat reservations, when user gets deleted.
                    if (is_numeric($user)) {
                        $db = App::instance()->db;
                        $db->where('user', $user);
                        $db->delete('forge_events_seat_reservations');
                    }
                }
            );
        }

        // google maps
        // // https://maps.googleapis.com/maps/api/js?key=AIzaSyCUhl24DMsrw9U02Q3hR6LGYF_6oYoqEx0
        $key = Settings::get('google_api_key');
        if (!$key) {
            Logger::debug('No Google API Key defined for maps.');
        } else {
            App::instance()->tm->theme->addScript('//maps.googleapis.com/maps/api/js?key=' . $key, true);
            App::instance()->tm->theme->addScript($this->url() . "assets/scripts/forge-events-map.js", true);
        }

        // backend
        Loader::instance()->addStyle("modules/forge-events/assets/css/forge-events.less");
        Loader::instance()->addScript("modules/forge-events/assets/scripts/forge-events.js");
        //Loader::instance()->addScript("modules/forge-events/assets/scripts/instascan.min.js");

        // frontend
        App::instance()->tm->theme->addScript($this->url() . "assets/scripts/forge-events.js", true);

        App::instance()->tm->theme->addScript(CORE_WWW_ROOT . "ressources/scripts/tablebar.js", true);
        App::instance()->tm->theme->addScript(CORE_WWW_ROOT . "ressources/scripts/externals/tooltipster.bundle.min.js", true);

        App::instance()->tm->theme->addStyle(MOD_ROOT . "forge-events/assets/css/forge-events.less");
        App::instance()->tm->theme->addStyle(MOD_ROOT . "forge-events/assets/css/event-block.less");
        App::instance()->tm->theme->addStyle(MOD_ROOT . "forge-events/assets/css/fe-event-detail.less");
        App::instance()->tm->theme->addStyle(CORE_WWW_ROOT . "ressources/css/externals/tooltipster.bundle.min.css");

        // register API
        API::instance()->register('forge-events', array($this, 'apiEventsAdapter'));
    }

    public function itemFilterOrderTable($filterValues) {
        $collection = App::instance()->cm->getCollection('forge-events');

        $newValues = [];
        foreach($collection->items() as $item) {
            $newValues['item:'.$item->id] = $item->getMeta('title');
        }

        return array_merge($filterValues, $newValues);
    }

    public function orderTableHeading($ths) {
        $ths[] = [
            'id' => 'ticket',
            'content' => i('Ticket', 'forge-events'),
            'class' => '',
            'cellAction' => ''
        ];
        return $ths;
    }

    public function orderTableRow($td, $args) {
        $td[] = [
            'id' => 'ticket',
            'content' => '<a target="blank" href="' . Utils::getUrl(['fe-ticket-print', $args['order']]) . '">' . i('Print Ticket', 'forge-events') . '</a>',
            'class' => '',
            'cellAction' => ''
        ];
        return $td;
    }

    public function apiEventsAdapter($data) {
        switch ($data['query'][0]) {
            case 'participants':
                $participants = new Participants($data['query'][1]);
                if (Auth::allowed('manage.forge-events', true)) {
                    $participants->isAdmin = true;
                }
                return $participants->handleQuery($data['query'][2]);
            case 'seatplan':
                if (array_key_exists('event', $data['data'])) {
                    $sp = new Seatplan($data['data']['event']);
                } else {
                    $sp = new Seatplan($data['query'][2]);
                }
                return $sp->handleRequest($data['query'], $data['data']);
            case 'ticket-buy':
                $step = new SignupStepBuy($data['query'][1]);
                if ($data['query'][2] == 'another-user') {
                    return json_encode($step->addAnotherUser($data['data']['buy-for-another-user']));
                }
                if ($data['query'][2] == 'buy-table') {
                    return json_encode(array("content" => $step->getBuyTable()));
                }
            default:
                return false;

        }
    }

    private function registerSettings() {
        $set = Settings::instance();
        $set->registerField(
            Fields::checkbox(array(
                'key' => 'forge-events-seatplan',
                'label' => i('Activate Seatplan Management', 'forge-events'),
                'hint' => i('If this checkbox is set, the seatplan management will be activated.', 'forge-events')
            ), Settings::get('forge-events-seatplan')), 'forge-events-seatplan', 'left', 'forge-events');

        $set->registerField(
            Fields::checkbox(array(
                'key' => 'forge-events-seatplan-locked',
                'label' => i('Ticket seat locked after finished signup.', 'forge-events'),
                'hint' => i('If this checkbox is set, the user won\'t be able to change its seat after completing the reservation.', 'forge-events')
            ), Settings::get('forge-events-seatplan-locked')), 'forge-events-seatplan-locked', 'left', 'forge-events');

        $set->registerField(
            Fields::text(array(
                'key' => 'forge-events-ticket-text-below-facts',
                'label' => i('Text below Facts', 'forge-events'),
                'hint' => i('Text below the facts in the pdf-ticket.', 'forge-events')
            ), Settings::get('forge-events-ticket-text-below-facts')), 'forge-events-ticket-text-below-facts', 'left', 'forge-events');

        $set->registerField(
            Fields::text(array(
                'key' => 'forge-events-ticket-footer-text',
                'label' => i('Footer Ticket Text', 'forge-events'),
                'hint' => i('Footer Text for the pdf ticket.', 'forge-events')
            ), Settings::get('forge-events-ticket-footer-text')), 'forge-events-ticket-footer-text', 'left', 'forge-events');
    }

    private function install() {
        if (Settings::get($this->name . ".installed")) {
            return;
        }

        App::instance()->db->rawQuery('CREATE TABLE IF NOT EXISTS `forge_events_seats` (' .
            '`id` int(11) NOT NULL,' .
            '`event` int(11) NOT NULL,' .
            '`x` char(2) NOT NULL,' .
            '`y` int(11) NOT NULL,' .
            '`type` varchar(100) NOT NULL' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        App::instance()->db->rawQuery('CREATE TABLE IF NOT EXISTS `forge_events_seat_reservations` (' .
            '`id` int(11) NOT NULL,' .
            '`user` int(11) NOT NULL,' .
            '`x` varchar(10) NOT NULL,' .
            '`y` int(11) NOT NULL,' .
            '`order_id` int(11) NOT NULL,' .
            '`event_id` int(11) NOT NULL,' .
            '`locked` int(11) NOT NULL DEFAULT \'0\'' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        App::instance()->db->rawQuery('ALTER TABLE `forge_events_seats` ADD PRIMARY KEY (`id`);');
        App::instance()->db->rawQuery('ALTER TABLE `forge_events_seat_reservations` ADD PRIMARY KEY (`id`), ADD KEY `event_id` (`event_id`), ADD KEY `order_id` (`order_id`);');
        App::instance()->db->rawQuery('ALTER TABLE `forge_events_seats` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
        App::instance()->db->rawQuery('ALTER TABLE `forge_events_seat_reservations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

        Settings::set($this->name . ".installed", 1);
    }
}

?>
