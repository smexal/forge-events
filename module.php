<?

class ForgeEvents extends Module {
    private $permission = 'manage.forge-events';

    public function setup() {
        $this->version = '0.1.0';
        $this->id = "forge-events";
        $this->name = i('Forge Events', 'forge-events');
        $this->description = i('Event Management for Forge.', 'forge-events');
        $this->image = $this->url().'assets/images/module-image.png';
    }

    public function start() {
        Auth::registerPermissions($this->permission);

        // always load these files
        Loader::instance()->loadDirectory(MOD_ROOT."forge-events/classes/");
        Loader::instance()->loadDirectory(MOD_ROOT."forge-events/views/");

        require_once($this->directory()."collection.event.php");

        // backend
        Loader::instance()->addStyle("modules/forge-events/assets/css/forge-events.less");
        Loader::instance()->addScript("modules/forge-events/assets/scripts/forge-events.js");

        // frontend
        App::instance()->tm->theme->addScript($this->url()."assets/scripts/forge-events.js", true);
        App::instance()->tm->theme->addScript(CORE_WWW_ROOT."scripts/externals/tooltipster.bundle.min.js", true);

        App::instance()->tm->theme->addStyle(MOD_ROOT."forge-events/assets/css/forge-events.less");
        App::instance()->tm->theme->addStyle(CORE_WWW_ROOT."css/externals/tooltipster.bundle.min.css");

        API::instance()->register('forge-events', array($this, 'apiAdapter'));
    }

    public function apiAdapter($data) {
        switch($data['query'][0]) {
            case 'seatplan':
                $sp = new Seatplan($data['data']['event']);
                return $sp->handleRequest($data['query'], $data['data']);
            case 'ticket-buy':
                $step = new SignupStepBuy($data['query'][1]);
                if($data['query'][2] == 'another-user') {
                    return $step->addAnotherUser($data['data']['buy-for-another-user']);
                }
            default:
                return false;
        }
    }

}

?>