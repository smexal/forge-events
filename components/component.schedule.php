<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Abstracts\Component;
use \Forge\Core\App\App;

class ScheduleComponent extends Component {
    public $settings = [];
    private $prefix = 'forge_events_schedule_';

    public function prefs() {
        $this->settings = [
            [
                'label' => i('Time', 'forge-events'),
                'hint' => '',
                'key' => $this->prefix.'time',
                'type' => 'text'
            ],
            [
                'label' => i('Entry', 'forge-events'),
                'hint' => '',
                'key' => $this->prefix.'entry',
                'type' => 'text'
            ],
            [
                'label' => i('Location', 'forge-events'),
                'hint' => '',
                'key' => $this->prefix.'location',
                'type' => 'text'
            ]
        ];
        return [
            'name' => i('Schedule Entry'),
            'description' => i('Add a schedule Entry Block.', 'forge-events'),
            'id' => 'forge_events_schedule',
            'image' => '',
            'level' => 'inner',
            'container' => false
        ];
    }

    public function content() {
        return App::instance()->render(DOC_ROOT.'modules/forge-events/templates/', "schedule-block", [
            'time' => $this->getField($this->prefix.'time'),
            'location' => $this->getField($this->prefix.'location'),
            'entry' => $this->getField($this->prefix.'entry')
        ]);
    }
}


?>
