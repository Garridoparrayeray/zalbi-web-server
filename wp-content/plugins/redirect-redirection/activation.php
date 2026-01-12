<?php

/**
 * This file is loaded only on plugin's activation
 */
if (!defined("ABSPATH")) {
    exit();
}

/**
 * Load contants and database manager
 */
include_once "includes/irrp-constants.php";
include_once "includes/irrp-db-manager.php";

/**
 * Make new database manager and try to create DB tables if does not exist
 */
$iRdbManager = new IRRPDBManager();
$iRdbManager->createTables(is_multisite());

/**
 * Mark activation option
 */
update_option('irrp_activation_redirect', true);
if (!defined('IRRP_ACTIVATION_REQUEST')) {
    define('IRRP_ACTIVATION_REQUEST', true);
}

//add_filter("cron_schedules", "irrpSetIntervals");

function irrpSetIntervals($schedules) {
    $schedules[IRRP_CRON_DELETE_LOGS_RECURRENCE_KEY] = [
        "interval" => IRRP_CRON_DELETE_LOGS_RECURRENCE,
        "display" => esc_html__("Every 15 minutes", "redirect-redirection")
    ];
    return $schedules;
}

if (!wp_next_scheduled(IRRP_CRON_DELETE_LOGS)) {
    //wp_schedule_event(current_time("timestamp", 1), IRRP_CRON_DELETE_LOGS_RECURRENCE_KEY, IRRP_CRON_DELETE_LOGS);
    wp_schedule_event(current_time("timestamp", 1), "twicedaily", IRRP_CRON_DELETE_LOGS);
}
