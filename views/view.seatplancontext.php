<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\View;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Logger;
use Forge\Modules\ForgeEvents\Participants;
use Forge\Modules\ForgePayment\Payment;

class SeatplancontextView extends View {

    public $permission = 'manage.forge-events';
    public $parent = 'manage';
    public $name = 'forge-event-seatplan-context';
    public $standalone = true;
    private $message = false;
    private $event = false;
    private $seatplan = false;
    private $x = false;
    private $y = false;


    public function content($uri = array()) {
        if(! Auth::allowed('manage.forge-events')) {
            return;
        }

        $this->x = $_GET['x'];
        $this->y = $_GET['y'];
        $this->event = $_GET['event'];
        $this->seatplan = new Seatplan($this->event);

        if(isset($_POST['player_for_seat'])) {
            // set new player on this seat.
            if($_POST['player_for_seat']  != '0') {
                $this->setPlayerSeat($_POST['player_for_seat']);

            // flush player seat if empty is selected.
            } else {
                $this->seatplan->flushSeatReservation($this->x, $this->y, $this->event);
            }

            if(isset($_POST['seat-type-selection']) && (
                    $_POST['seat-type-selection'] != $this->seatplan->getSeatStatus($this->x, $this->y) ||
                    $_POST['seat-type-value--'.$_POST['seat-type-selection']] != ''
            )) {
                $data = [
                    'x' => $this->x,
                    'y' => $this->y,
                    'event' => $this->event,
                    'type' => $_POST['seat-type-selection'],
                    'value' => $_POST['seat-type-value--'.$_POST['seat-type-selection']]
                ];
                $this->seatplan->updateSeatData($data);
            }

            App::instance()->redirect(Utils::getUrl(array('manage', 'collections', 'forge-events', 'edit', $this->event, 'seatplan')));
        }


        return App::instance()->render(CORE_TEMPLATE_DIR."views/parts/", "crud.modify", array(
            'title' => sprintf(i('Manage Seat `%s`'), $this->x.':'.$this->y),
            'message' => $this->message,
            'form' => $this->getModForm()
        ));
    }

    private function getModForm() {
        $return = '<form action="'.Utils::getUrl(Utils::getUriComponents(), true).'" method="post" class="ajax">';

        $user = $this->seatplan->getSoldUser($this->x, $this->y);
        $return.= Fields::select([
            'chosen' => true,
            'key' => 'player_for_seat',
            'label' => i('Choose a player for this seat.', 'forge-events'),
            'values' => $this->getEligibleParticipants(),
            ''
        ], $user ? $user->get('id') : 0);

        $return.= '<hr />';

        $return.= '<h4>'.i('Set seat type', 'forge-events').'</h4>';
        $return.= '<ul>';
        $currentStatus = $this->seatplan->getSeatStatus($this->x, $this->y);
        foreach($this->seatplan->seatStatus as $seatType) {
            $return.= '<li class="card"><label><input type="radio" value="'.$seatType.'" '.($currentStatus == $seatType ? "checked=\"checked\"" : '').' name="seat-type-selection"><span>'.i($seatType, 'forge-events').'</label></span>';
            if($seatType == 'icon' || $seatType == 'character') {
                $value = '';
                if($currentStatus == $seatType) {
                    $value = $this->seatplan->getSeatValue($this->x, $this->y);
                }
                $return.= ' <input type="text" value="'.$value.'" name="seat-type-value--'.$seatType.'" />';
                if($seatType == 'icon') {
                    $return.= '<code>icon-[wall-[t/r/b/ö]/door] + deg-[0/90/180/270] + mirror-[hor/ver]</code>';
                }
            }
            $return.='</li>';
        }
        $return.= '</ul>';
 
        $return.= Fields::button(i('Save changes', 'core'));

        $return.= '</form>';
        return $return;
    }

    private function setPlayerSeat($user) {
        // get order....
        $collection = App::instance()->cm->getCollection('forge-events');
        $order = $collection->getOrderByUser($this->event, $user);

        if(! is_array($order)) {
            return;
        }

        $order = $order['id'];
        $this->seatplan = new Seatplan($this->event);
        $this->seatplan->flushSeatReservation($this->x, $this->y, $this->event);
        $this->seatplan->saveReservation($user, $this->x, $this->y, $order, $this->event);
    }

    private function getEligibleParticipants() {
        $participants = new Participants($this->event);
        $eligibleParticipants = [];
        $eligibleParticipants[0] = i('Empty');
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
