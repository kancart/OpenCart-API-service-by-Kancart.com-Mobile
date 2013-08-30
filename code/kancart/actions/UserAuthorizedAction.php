<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class UserAuthorizedAction extends BaseAction {

    public function validate() {
 
        if (!parent::validate()) {
            return false;
        }
        if ($this->customer->isLogged()) {
            return true;
        }
        $this->setError(KancartResult::ERROR_SYSTEM_INVALID_SESSION_KEY);
        return false;
    }

}

?>
