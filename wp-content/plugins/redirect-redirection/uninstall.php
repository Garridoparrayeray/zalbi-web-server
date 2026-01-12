<?php

if (!defined("ABSPATH")) {
    exit();
}

include_once "includes/irrp-constants.php";
include_once "includes/irrp-db-manager.php";

// if uninstall.php is not called by WordPress, die
if (!defined("WP_UNINSTALL_PLUGIN")) {
    die;
}

delete_option(IRRPConstants::OPTIONS_MAIN);
delete_site_option(IRRPConstants::OPTIONS_MAIN);

$dbManager = new IRRPDBManager();
$dbManager->dropTables();
