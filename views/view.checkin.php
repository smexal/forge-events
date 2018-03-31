<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\View;
use Forge\Core\App\Auth;

class CheckinView extends View {

    public $permission = 'manage.checkin';
    public $parent = 'manage';
    public $name = 'checkin';
    public $standalone = true;

    public function content($uri = array()) {
        return $_GET['id'];
    }
}
