<?php

/**
 * This file is loaded only on plugin's deactivation
 */
if (!defined("ABSPATH")) {
    exit();
}

if (wp_next_scheduled(IRRP_CRON_DELETE_LOGS)) {
    wp_clear_scheduled_hook(IRRP_CRON_DELETE_LOGS);
}