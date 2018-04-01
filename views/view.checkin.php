<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Abstracts\View;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;
use Forge\Modules\ForgePayment\Payment;

class CheckinView extends View {

    public $permission = 'manage.checkin';
    public $parent = 'manage';
    public $name = 'checkin';
    public $standalone = true;

    public function content($uri = array()) {
        /**
         * ALTER TABLE `forge_events_seat_reservations` ADD `checkin` DATETIME NULL AFTER `locked`;
         */

        $orderId = Utils::decodeBase64($_GET['id']);
        $eventId = Utils::decodeBase64($_GET['e']);
        $userId = Utils::decodeBase64($_GET['u']);

        $order = Payment::getOrder($orderId);
        $status_message = '';
        $additional_message = '';
        $status = 'error';
        if(is_null($order->data['paymentMeta'])) {
            $status_message = i('No Order found', 'forge-events');
            $additional_message = 'ID: '.$orderId;
        }

        // check if order is paid...
        $payment_status = Payment::getStatus($orderId);
        if($payment_status != 'success') {
            $status_message = i('not paid', 'forge-events');
            $additional_message = 'Payment Status: '.$payment_status;
        }

        // check if user has a seat on the seat plan (event)
        $seatplan = new Seatplan($eventId);
        $seat = $seatplan->getUserSeat($userId);
        if($seat != '') {
            $checkin = $seatplan->checkinTime($userId);
            if(is_null($checkin)) {
                $status = 'success';
                $status_message = i('Checkin successful', 'forge-events');
                $user = new User($userId);
                $additional_message = '<br /><small>'.i('Seat', 'forge-events').'</small><br /> '.$seat.'<br /><br /><small>'.i('User', 'forge-events').'<br /></small> '.$user->get('username');
                $seatplan->checkin($userId);
            } else {
                $status = 'warning';
                $status_message = i('Already Checked In', 'forge-events');
                $additional_message = i('This user has checked in at ', 'forge-events').$checkin;
            }
        }

        // check if user is not already checked in

        // if all yes => check in the user for this event

        return App::instance()->render(MOD_ROOT.'forge-events/templates/parts', 'checkin', [
            'title' => i('Checkin', 'forge-events'),
            'status_message' => $status_message,
            'status' => $status,
            'additional_message' => $additional_message
        ]);
    }
}
