<?php

class SignupStepUser {
    public $id = 'signup-user';

    public function content() {
        if(is_null(App::instance()->user)) {
            return $this->loginView();
        }
        if(App::instance()->user->get('active') == 0) {
            return $this->verificationView();
        }
        return $this->verifiedUser();
    }

    private function verifiedUser() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "user", array(
                'verified' => array(
                    'title' => i('User verified', 'forge-events'),
                    'text' => i('You\'re all set to buy a ticket.'),
                    'link' => Utils::getUrl(Utils::getUriComponents()),
                    'linktext' => i('Buy a ticket')
                ),
                'login' => false,
                'verification' => false
        ));
    }

    private function verificationView() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "user", array(
                'verified' => false,
                'login' => false,
                'verification' => array(
                    'title' => i('User Verification', 'forge-events'),
                    'text' => i('It seems like your e-mail address has yet not been verified. You have to verify user e-mail address before you can buy a ticket.'),
                    'resendurl' => Utils::getUrl(array('registration', 'resend-verification')),
                    'resendtext' => i('Resend verification link', 'forge-events'),
                    'email' => sprintf(i('We sent you the verification link to: <strong>%s</strong>'), App::instance()->user->get('email'))
                )
        ));
    }

    private function loginView() {
        return App::instance()->render(MOD_ROOT."forge-events/templates/steps/", "user", array(
            'verified' => false,
            'verification' => false,
            'login' => array(
                'title' => i('Login', 'forge-events'),
                'intro' => i('You have to login with a verified user to buy a ticket.', 'forge-events'),
                'form' => Login::instance()->form(false)
            ),
            'register' => array(
                'title' => i('Registration', 'forge-events'),
                'intro' => i('You can also create a new user, if you do not already have one.', 'forge-events'),
                'url' => Utils::getUrl(array('registration')),
                'linktext' => i('Start Registration', 'forge-events')
            ),
            'recover' => array(
                'title' => i('Recover Account', 'forge-events'),
                'intro' => i('If you forgot your credentials, we help you to recover your account.', 'forge-events'),
                'url' => Utils::getUrl(array('recover')),
                'linktext' => i('Recover account', 'forge-events')
            )
        ));
    }

    public function title() {
        return i('1. Verify user', 'forge-events');
    }

    public function allowed() {
        return true;
    }


}

?>