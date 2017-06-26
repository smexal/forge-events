<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Abstracts\View;
use \Forge\Core\App\App;
use \Forge\Core\App\App\CollectionItem;
use \Forge\Core\Classes\Fields;



class SignupView extends View {
    public $name = 'event-signup';
    public $allowNavigation = true;
    private $event = null;
    private $signup = false;

    public function additionalNavigationForm() {
        $events = App::instance()->cm->getCollection('forge-events')->items();
        $values = array();
        foreach($events as $event) {
            $values[$event->slug()] = $event->getMeta('title');
        }
        $formfields = Fields::select(array(
            'key' => 'add-to-url',
            'label' => i('Select the event, that you want to display the signup view.'),
            'values' => $values
        ));
        return array("form" => $formfields);
    }

    public function content($parts = array()) {
        $collection = App::instance()->cm->getCollection('forge-events');
        $this->event = $collection->getBySlug($parts[0]);
        $this->signup = new Signup($this->event);

        return App::instance()->render(MOD_ROOT."forge-events/templates/", "signup", array(
            'title' => sprintf(i('Signup for %s'), $this->event->getMeta('title')),
            'steps' => $this->signup->getSteps(),
            'stepcontent' => $this->signup->getContents()
        ));
    }
}
