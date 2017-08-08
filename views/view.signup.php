<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\Abstracts\View;
use \Forge\Core\App\App;
use \Forge\Core\App\App\CollectionItem;
use \Forge\Core\Classes\Fields;
use \Forge\Core\Classes\Utils;



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

        if( $this->event->getMeta('allow-signup') == false || $this->event->getMeta('status') == 'draft') {
            return;
        }

        $this->signup = new Signup($this->event);

        if(count($parts) > 1 && $parts[1] == 'complete-order') {
            $this->completeOrder();
        }

        return App::instance()->render(MOD_ROOT."forge-events/templates/", "signup", array(
            'title' => sprintf(i('Signup for %s'), $this->event->getMeta('title')),
            'steps' => $this->signup->getSteps(),
            'stepcontent' => $this->signup->getContents()
        ));
    }

    private function completeOrder() {
        $signupSeats = new SignupStepSeat($this->event);
        $buyedSeats = $signupSeats->getBuyedSeats();
        foreach($buyedSeats as $seat) {
            if(! $seat['seatSet']) {
                App::instance()->addMessage(
                    i('You have buyed seats without a place set. Choose a place before finishing.', 'forge-events')
                );
                App::instance()->redirect(Utils::url(['event-signup', $this->event->slug()]));
            }
        }
        // everythin okay, no redirect...
        // "fix" the seats in the database
        $db = App::instance()->db;
        foreach($buyedSeats  as $seat) {
            $db->where('id', $seat['seatId']);
            $data = ['locked' => 1];
            $db->update('forge_events_seat_reservations', $data);
        }
        App::instance()->addMessage(i('Registration complete, we\'re looking forward to meet you at the event.', 'success'));
        // redirect to orders...
        App::instance()->redirect(Utils::url(['orders']));
    }
}
