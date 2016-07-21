<?php 
class ForgeEventSignup extends AbstractView {
    public $name = 'event-signup';
    public $allowNavigation = true;
    private $event = null;

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

        return App::instance()->render(MOD_ROOT."forge-events/templates/", "signup", array(
            'title' => sprintf(i('Signup for %s'), $this->event->getMeta('title')),
            'steps' => $this->getSteps()
        ));
    }

    private function getSteps() {
        return array(
            array(
                'active' => true,
                'title' => i('1. Verify users', 'forge-events')
            ),
            array(
                'active' => false,
                'title' => i('2. Buy tickets', 'forge-events')
            ),
            array(
                'active' => false,
                'title' => i('3. Reserve your seats', 'forge-events')
            )
        );
    }
}