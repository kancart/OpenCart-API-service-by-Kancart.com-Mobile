<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_isexists_action extends BaseAction {

    public function execute() {
        $username = is_null($this->getParam('email')) ? '' : trim($this->getParam('email'));
        if (strlen($username) == 0) {
            $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER);
            return;
        }
        $response = array();
        $response['nick_is_exist'] = "false";
        $response['uname_is_exist'] = "false";
        $userService = ServiceFactory::factory('User');
        if ($userService->checkEmailExists($username)) {
            $response['uname_is_exist'] = "true";
        }
        $this->setSuccess($response);
    }

}

?>
