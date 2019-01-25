<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\View;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;
use Forge\Core\Classes\Fields;
use Forge\Modules\ForgeEvents\Participants;
use Forge\Modules\ForgePayment\Payment;

class SeatplancontextView extends View {

    public $permission = 'manage.forge-events';
    public $parent = 'manage';
    public $name = 'forge-event-seatplan-context';
    public $standalone = true;
    private $message = false;
    private $event = false;


    public function content($uri = array()) {
        if(! Auth::allowed('manage.forge-events')) {
            return;
        }

        $x = $_GET['x'];
        $y = $_GET['y'];
        $this->event = $_GET['event'];

        if(isset($_POST['player_for_seat'])) {
            $this->setPlayerSeat($x, $y, $_POST['player_for_seat']);
        }
        
        return App::instance()->render(CORE_TEMPLATE_DIR."views/parts/", "crud.modify", array(
            'title' => sprintf(i('Manage Seat `%s`'), $x.':'.$y),
            'message' => $this->message,
            'form' => $this->getModForm()
        ));
    }

    private function getModForm() {
        $return = '<form action="'.Utils::getUrl(Utils::getUriComponents(), true).'" method="post" class="ajax">';

        $return.= Fields::select([
            'chosen' => true,
            'key' => 'player_for_seat',
            'label' => i('Choose a player for this seat.', 'forge-events'),
            'values' => $this->getEligibleParticipants()
        ]);

        $return.= Fields::button(i('Save changes', 'core'));

        $return.= '</form>';
        return $return;
    }

    private function setPlayerSeat($x, $y, $user) {
        // get order....
        $collection = App::instance()->cm->getCollection('forge-events');
        $order = $collection->getOrderByUser($this->event, $user);

        if(! is_array($order)) {
            return;
        }

        $order = $order['id'];
        $seatplan = new Seatplan($this->event);
        $seatplan->flushSeatReservation($x, $y, $this->event);
        $seatplan->saveReservation($user, $x, $y, $order, $this->event);

        App::instance()->redirect(Utils::getUrl(array('manage', 'collections', 'forge-events', 'edit', $this->event, 'seatplan')));
    }

    private function getEligibleParticipants() {
        $participants = new Participants($this->event);
        $eligibleParticipants = [];
        foreach ($participants->getAll() as $participant) {
            $seat = $participant['seat'];
            if($seat) {
                $seat = sprintf(i('Current Seat: %s', 'forge-events'), $seat);
            } else {
                $seat = i('No current seat', 'forge-events');
            }
            $eligibleParticipants[$participant['user']['id']] = $participant['user']['username'].' ('.$participant['user']['email'].') '.$seat;
        }
        return $eligibleParticipants;
    }
}
