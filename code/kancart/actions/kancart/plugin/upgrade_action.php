<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_plugin_upgrade_action extends BaseAction {

    public function execute() {
        if (isset($_REQUEST['do_upgrade'])) {
            PluginUpgrader::upgrade();
        } else {
            kc_include(KANCART_ROOT . '/upgrade/upgrade_interface.php');
            die();
        }
    }

}

// end