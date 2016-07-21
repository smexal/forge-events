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
        Loader::instance()->loadDirectory(MOD_ROOT."forge-events/views/");

        require_once($this->directory()."collection.event.php");
        require_once($this->directory()."classes/class.seatplan.php");
        Loader::instance()->addStyle("modules/forge-events/assets/css/forge-events.less", false, "manage");
        Loader::instance()->addScript("modules/forge-events/assets/scripts/forge-events.js");

        API::instance()->register('forge-events', array($this, 'apiAdapter'));
    }

    public function apiAdapter($data) {
        if(! Auth::allowed($this->permission, false)) {
            return false;
        }

        switch($data['query'][0]) {
            case 'seatplan':
                $sp = new Seatplan($data['data']['event']);
                return $sp->handleRequest($data['query'], $data['data']);
            default:
                return false;
        }
    }

}

?>