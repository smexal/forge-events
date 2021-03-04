<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Core\App\App;
use \Forge\Core\Classes\Utils;
use \Forge\Views\LoginView;



class SignupStepSuccess {
    public $id = 'signup-success';

    public function content() {
        $content = '<h2>'.i('Thank you for your order', 'forge-events').'</h2>';
        $content.= '<p>'.i('Your order has been completed. If you directly paid, you can now find your ticket in your orders or in your email. Otherwise you will get a email with all further required information.', 'forge-eventes');
        return $content;
    }

    public function title() {
        return i('3. Success', 'forge-events');
    }

    public function allowed() {
        if(array_key_exists('order', $_GET) && $_GET['order'] === 'success') {
            return true;
        }
        return false;
    }


}

?>
