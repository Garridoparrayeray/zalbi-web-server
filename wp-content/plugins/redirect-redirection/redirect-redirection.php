<?php
/*
 * Plugin Name: Redirect Redirection
 * Description: Create specific URL redirections and redirection rules super-easily on a beautiful, user-friendly interface of the Redirect Redirection plugin.
 * Version: 1.2.7
 * Author: Inisev
 * Author URI: https://inisev.com
 * Plugin URI: https://redirection.pro
 * Text Domain: redirect-redirection
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Plugin's constants
 */
define("IRRP_DIR_PATH", dirname(__FILE__));
define("IRRP_DIR_NAME", basename(IRRP_DIR_PATH));
define("IRRP_CRON_DELETE_LOGS", "irrp_cron_delete_logs");
define("IRRP_CRON_DELETE_LOGS_RECURRENCE_KEY", "irrp_custom_interval");
define("IRRP_CRON_DELETE_LOGS_RECURRENCE", 60);
define("IRRP_PLUGIN_VERSION", "1.2.7");

/**
 * Create tables on activation.
 */
register_activation_hook(__FILE__, function () {

    include_once IRRP_DIR_PATH . '/activation.php';
});

/**
 * Disable CRON job on deactivation
 */
register_deactivation_hook(__FILE__, function () {

    include_once IRRP_DIR_PATH . '/deactivation.php';
});

/**
 * Load the plugin
 */
add_action('wp_loaded', function () {

    // Include our cool banner
    include_once "includes/banner/misc.php";

    include_once "includes/irrp-constants.php";
    include_once "includes/irrp-db-manager.php";
    include_once "includes/irrp-helper.php";
    include_once "includes/settings/irrp-settings.php";
    include_once "includes/irrp-helper-ajax.php";
    include_once "includes/irrp-export-import.php";

    class IrrPRedirection implements IRRPConstants {

        /**
         * @var IRRPDBManager
         */
        private $dbManager;

        /**
         * @var IRRPSettings
         */
        private $settings;

        /**
         * @var IRRPHelper
         */
        private $helper;

        /**
         * @var IRRPHelperAjax
         */
        private $helperAjax;

        /**
         * @var IRRPExportImport
         */
        private $exportImport;
        private $minimum_php_version = '7.0.0';
        private $minimum_wp_version = '5.0';
        public static $TABS = [
            "specific-url-redirections" => "specific-url-redirections",
            "redirection-rules" => "redirection-rules",
            "redirection-and-404-logs" => "redirection-and-404-logs",
            "automatic-redirects" => "automatic-redirects",
            "change-urls" => "change-urls",
        ];
        public static $CRITERIAS;
        public static $PERMALINK_STRUCTURE_VALUES;
        public static $ACTIONS;
        public static $REDIRECTION_LOGS_DELETE;
        public static $REDIRECTION_LOGS_FILTER;
        private static $INSTANCE = null;

        private function __construct() {

            $this->dbManager = new IRRPDBManager();
            //add_action("admin_notices", [&$this, "adminNotices"]);
            add_action("wp_loaded", [&$this, "irrpDependencies"]);
            //add_filter("cron_schedules", [$this, "irrpSetIntervals"]);
        }

        public static function getInstance() {
            if (is_null(self::$INSTANCE)) {
                self::$INSTANCE = new self();
            }
            return self::$INSTANCE;
        }

        /**
         * Add a signature to the front page.
         */
        public function MetaVersion() {
            echo '<meta name="redi-version" content="' . IRRP_PLUGIN_VERSION . '" />';
        }

        public function irrpInit() {
        	$rules = $this->dbManager->getRules();
            $option404Status = $this->dbManager->isAre404sRuleExists($rules) ? "disabled" : "";
            $optionAllUrlsStatus = $this->dbManager->isAllURLsRuleExists($rules) ? "disabled" : "";

            self::$CRITERIAS = [
                [
                    "option" => "contain",
                    "text" => __("Contain", "redirect-redirection"),
                ],
                [
                    "option" => "start-with",
                    "text" => __("Start with", "redirect-redirection"),
                ],
                [
                    "option" => "end-with",
                    "text" => __("End with", "redirect-redirection"),
                ],
                [
                    "option" => "have-permalink-structure",
                    "text" => __("Have permalink structure", "redirect-redirection"),
                ],
                // [
                //     "option" => "have-category",
                //     "text" => __("Have Category", "redirect-redirection"),
                // ],
                // [
                //     "option" => "have-tag",
                //     "text" => __("Have Tag", "redirect-redirection"),
                // ],
                // [
                //     "option" => "have-author",
                //     "text" => __("Have Author", "redirect-redirection"),
                // ],
                [
                    "option" => "regex-match",
                    "text" => __("Regex matches", "redirect-redirection"),
                ],
                [
                    "option" => "are-404s",
                    "text" => __("Are 404s", "redirect-redirection"),
                    "status" => $option404Status,
                ],
	            [
		            "option" => "all-urls",
		            "text" => __("All URLs", "redirect-redirection"),
		            "status" => $optionAllUrlsStatus,
	            ],
            ];

            self::$PERMALINK_STRUCTURE_VALUES = [
                [
                    "option" => "day-and-name",
                    "text" => __("Day and name", "redirect-redirection")
                ],
                [
                    "option" => "month-and-name",
                    "text" => __("Month and name", "redirect-redirection")
                ],
                [
                    "option" => "post-name",
                    "text" => __("Post name", "redirect-redirection")
                ],
                [
                    "option" => "category-and-name",
                    "text" => __("Category and name", "redirect-redirection")
                ],
                [
                    "option" => "author-and-name",
                    "text" => __("Author and name", "redirect-redirection")
                ],
            ];

            self::$ACTIONS = [
                [
                    "option" => "a-specific-url",
                    "text" => __("A Specific URL", "redirect-redirection")
                ],
                [
                    "option" => "urls-with-new-string",
                    "text" => __("URLs with new string", "redirect-redirection")
                ],
                [
                    "option" => "urls-with-removed-string",
                    "text" => __("URLs with removed string", "redirect-redirection")
                ],
                [
                    "option" => "new-permalink-structure",
                    "text" => __("New permalink structure", "redirect-redirection")
                ],
                [
                    "option" => "regex-match",
                    "text" => __("Regex matches", "redirect-redirection")
                ],
                [
                    "option" => "random-similar-post",
                    "text" => __("Random similar post", "redirect-redirection")
                ],
                [
                    "option" => "explain-those-options",
                    "text" => __("Explain those options", "redirect-redirection")
                ],
            ];

            self::$REDIRECTION_LOGS_DELETE = [
                [
                    "option" => "never",
                    "text" => __("Never", "redirect-redirection"),
                ],
                [
                    "option" => "older-than-a-week",
                    "text" => __("Older than a week", "redirect-redirection")
                ],
                [
                    "option" => "older-than-a-month",
                    "text" => __("Older than a month", "redirect-redirection")
                ],
                "selectedId" => 0,
            ];

            self::$REDIRECTION_LOGS_FILTER = [
                [
                    "option" => "all",
                    "text" => __("All", "redirect-redirection"),
                ],
                [
                    "option" => "404s",
                    "text" => __("404s", "redirect-redirection")
                ],
                "selectedId" => 0,
            ];
        }

        public function irrpDependencies() {
            if (!defined('IRRP_ACTIVATION_REQUEST') && get_option('irrp_activation_redirect', false) == true) {
                delete_option('irrp_activation_redirect');
                wp_redirect(admin_url('admin.php?page=irrp-redirection'));
                exit;
            }

            $this->helper = new IRRPHelper($this->dbManager);
            $this->settings = new IRRPSettings($this->dbManager, $this->helper);
            $this->helperAjax = new IRRPHelperAjax($this->dbManager, $this->settings, $this->helper);
            $this->exportImport = new IRRPExportImport($this->dbManager, $this->helper);

            add_action("wpmu_new_blog", [&$this->dbManager, "onNewBlog"], 10, 6);
            add_filter("wpmu_drop_tables", [&$this->dbManager, "onDeleteBlog"]);

            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", [&$this, "links"]);

            add_action("activated_plugin", [&$this, "activated"]);
            add_action("admin_post_ir_uninstall", [&$this, "uninstall"]);

            // Add signature to frontend.
            add_action("wp_head", [&$this, "MetaVersion"]);
        }

        function irrpSetIntervals($schedules) {
            $schedules[IRRP_CRON_DELETE_LOGS_RECURRENCE_KEY] = [
                "interval" => IRRP_CRON_DELETE_LOGS_RECURRENCE,
                "display" => esc_html__("Every 15 minutes", "redirect-redirection")
            ];
            return $schedules;
        }

        public function activated($plugin) {
            if ($plugin == plugin_basename(__FILE__)) {
                exit(wp_redirect(admin_url("admin.php?page=" . self::PAGE_SETTINGS)));
            }
        }

        public function adminNotices() {
            $wpVersion = get_bloginfo("version");
            $phpVersion = phpversion();
            if (current_user_can("manage_options") || current_user_can("redirect_redirection_admin")) {
                if (version_compare($wpVersion, $this->minimum_wp_version, "<")) {
                    echo "<div class='error' style='padding:10px;'>" . __("Required minimum version of WordPress is : ", "redirect-redirection") . $this->minimum_wp_version . "</div>";
                }

                if (version_compare($phpVersion, $this->minimum_php_version, "<")) {
                    echo "<div class='error' style='padding:10px;'>" . __("Required minimum version of PHP is : ", "redirect-redirection") . $this->minimum_php_version . "</div>";
                }
            }
        }

        public function links($links) {
            $links[] = "<a href='" . esc_url_raw(admin_url("admin.php?page=" . self::PAGE_SETTINGS)) . "'>" . esc_html__("Manage", "redirect-redirection") . "</a>";
            $links[] = "<a class='ir-confirm-uninstall' href='" . esc_url_raw(admin_url("admin-post.php?action=ir_uninstall&nonce=" . wp_create_nonce('redi_uninstall'))) . "'>" . esc_html__("Reset", "redirect-redirection") . "</a>";
            return $links;
        }

        public function uninstall() {
            if (current_user_can("manage_options") || current_user_can("redirect_redirection_admin")) {

                if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'redi_uninstall')) {
                    return wp_send_json_error();
                }

                delete_option(self::OPTIONS_MAIN);
                delete_site_option(self::OPTIONS_MAIN);

                $this->dbManager->dropTables();

                deactivate_plugins(IRRP_DIR_NAME . "/" . basename(__FILE__));

                die(wp_redirect(admin_url("plugins.php")));

            }
        }

    }

    $irrPRedirection = IrrPRedirection::getInstance();
    $irrPRedirection->irrpInit();
    
    // Review banner
    if (!(class_exists('Inisev\Subs\Inisev_Review') || class_exists('Inisev_Review'))) require_once __DIR__ . '/modules/review/review.php';
    $review_banner = new \Inisev\Subs\Inisev_Review(__FILE__, __DIR__, 'redirect-redirection', 'Redirection', 'https://bit.ly/3Pw8VrS', 'irrp-redirection');
    
}, 9);

/**
 * Analyst module
 */
require_once 'analyst/main.php';

analyst_init(array(
'client-id' => 'pn8rx3lm7r4wamge',
'client-secret' => '9ccc7bfaa97519a4ac96c0214994a90b2de4f3c8',
'base-dir' => __FILE__
));
