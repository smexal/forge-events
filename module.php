<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Loader;
use \Forge\Core\Abstracts\Module;
use \Forge\Core\App\API;
use \Forge\Core\App\App;
use \Forge\Core\App\Auth;



class ForgeEvents extends Module {
    private $permission = 'manage.forge-events';

    public function setup() {
        $this->version = '0.1.0';
        $this->id = "forge-events";
        $this->name = i('Event Management', 'forge-events');
        $this->description = i('Event Management for Forge.', 'forge-events');
        $this->image = $this->url().'assets/images/module-image.png';
    }

    public function start() {
        Auth::registerPermissions($this->permission);
        Auth::registerPermissions("manage.forge-events.ticket-status.view");
        Auth::registerPermissions("manage.forge-events.ticket-status.edit");

        // backend
        Loader::instance()->addStyle("modules/forge-events/assets/css/forge-events.less");
        Loader::instance()->addScript("modules/forge-events/assets/scripts/forge-events.js");

        // frontend
        App::instance()->tm->theme->addScript($this->url()."assets/scripts/forge-events.js", true);
        App::instance()->tm->theme->addScript(CORE_WWW_ROOT."ressources/scripts/externals/tooltipster.bundle.min.js", true);

        App::instance()->tm->theme->addStyle(MOD_ROOT."forge-events/assets/css/forge-events.less");
        App::instance()->tm->theme->addStyle(CORE_WWW_ROOT."ressources/css/externals/tooltipster.bundle.min.css");

        API::instance()->register('forge-events', array($this, 'apiAdapter'));
    }

    public function apiAdapter($data) {
        switch ($data['query'][0]) {
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
}

?>
