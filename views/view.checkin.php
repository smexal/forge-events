<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\View;
use Forge\Core\App\Auth;

class CheckinView extends View
{

    public $permission = 'manage.checkin';
    public $parent = 'manage';
    public $name = 'checkin';
    public $standalone = true;

    public function content($uri = array())
    {
        if (Auth::allowed($this->permission)) {
            return $this->app->render(MOD_ROOT . 'forge-events/templates/', "checkin", array(
                'title' => i('Checkin / QR Code scanning', 'forge-events'),
                'button_text' => i('New Scan', 'forge-events')
            ));
        }
    }
}
