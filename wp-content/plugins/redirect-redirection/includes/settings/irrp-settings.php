<?php

if (!defined("ABSPATH")) {
    exit();
}

class IRRPSettings implements IRRPConstants {

    /**
     * @var IRRPDBManager
     */
    private $dbManager;

    /**
     * @var IRRPHelper
     */
    private $helper;
    private $data;

    public function __construct($dbManager, $helper) {

        if (!current_user_can('manage_options') && !current_user_can('redirect_redirection_admin')) {
          return;
        }

        $this->dbManager = $dbManager;
        $this->helper = $helper;

        add_option(self::OPTIONS_MAIN, $this->getDefaultSettings(), "", "no");
        add_option(self::OPTIONS_AUTO_REDIRECTS, $this->getDefaultAutoRedirects(), "", "yes");
        add_option(self::OPTIONS_LOGS_STATUS, "1", "", "yes"); 
        $this->initSettings();

        add_action("admin_menu", [&$this, "settingsPage"], 1);
        add_action("admin_enqueue_scripts", [&$this, "backendScripts"]);

        // AJAX
        add_action("wp_ajax_irLoadTab", [&$this, "loadTab"]);
        add_action("wp_ajax_irLoadSettings", [&$this, "loadSettings"]);
        add_action("wp_ajax_irSaveSettings", [&$this, "saveSettings"]);
        add_action("wp_ajax_irLogMeWhereIFinished", [&$this, "logMeWhereIFinished"]);
        //$this->ajaxActions();

        // FILTERS
        add_filter("irrp_log_requests", [&$this, "logRequests"]);
    }

    public function getDefaultSettings() {
        $settings = [
            "ignore_trailing_slashes" => "1",
            "ignore_parameters" => "1",
            "ignore_case" => "1",
            "pass_on_parameters" => "0",
            "redirect_code" => "301",
            "inclusion_exclusion_rules" => "0",
            "redirect_options" => "are_case",
            "redirection_http_headers" => "",
            "rules_group1" => ["enabled" => "0", "login_info" => ""],
            "rules_group2" => ["enabled" => "0", "role" => "", "role_name" => ""],
            "rules_group3" => ["enabled" => "0", "referrer" => "", "referrer_value" => "", "referrer_regex" => "0"],
            "rules_group4" => ["enabled" => "0", "agent" => "", "agent_value" => "", "agent_regex" => "0"],
            "rules_group5" => ["enabled" => "0", "cookie" => "", "cookie_name" => "", "cookie_value" => "", "cookie_regex" => "0"],
            "rules_group6" => ["enabled" => "0", "ip" => "", "ip_value" => ""],
            "rules_group7" => ["enabled" => "0", "server" => "", "server_value" => ""],
            "rules_group8" => ["enabled" => "0", "language" => "", "language_value" => ""],
        ];
        return $settings;
    }

    public function getDefaultAutoRedirects() {
        $settings = [
            "post_url"                  => "0",
            "page_url"                  => "0",
            "custom_posts"              => "0",
            "log_me_where_i_finished"   => "0"
        ];
        return $settings;
    }

    public function initSettings() {
        $this->setData(get_option(self::OPTIONS_MAIN, $this->getDefaultSettings()));
    }

    public function settingsPage() {
      if (current_user_can("manage_options")) {
        add_menu_page(
                __("Redirection", "redirect-redirection"),
                __("Redirection", "redirect-redirection"),
                "manage_options",
                self::PAGE_SETTINGS,
                [&$this, "settingsForm"],
                "dashicons-controls-repeat",
                90
        );
      } else if (current_user_can("redirect_redirection_admin")) {
        add_menu_page(
                __("Redirection", "redirect-redirection"),
                __("Redirection", "redirect-redirection"),
                "redirect_redirection_admin",
                self::PAGE_SETTINGS,
                [&$this, "settingsForm"],
                "dashicons-controls-repeat",
                90
        );
      }
    }

    public function backendScripts() {
        global $pagenow;

        $permalinkStructure = get_option("permalink_structure");
        $permalinkRelation = [
            "day-and-name" => ["/%year%/%monthnum%/%day%/%postname%/", "/%year%/%monthnum%/%day%/%postname%", "/index.php/%year%/%monthnum%/%day%/%postname%/", "/index.php/%year%/%monthnum%/%day%/%postname%"],
            "month-and-name" => ["/%year%/%monthnum%/%postname%/", "/%year%/%monthnum%/%postname%", "/index.php/%year%/%monthnum%/%postname%/", "/index.php/%year%/%monthnum%/%postname%"],
            "post-name" => ["/%postname%/", "/%postname%", "/index.php/%postname%/", "/index.php/%postname%"],
            "category-and-name" => ["/%category%/%postname%/", "/%category%/%postname%", "/index.php/%category%/%postname%/", "/index.php/%category%/%postname%"],
            "author-and-name" => ["/%author%/%postname%/", "/%author%/%postname%", "/index.php/%author%/%postname%/", "/index.php/%author%/%postname%"],
        ];

        foreach ($permalinkRelation as $prKey => $prValue) {
            if (in_array($permalinkStructure, $prValue)) {
                $permalinkStructure = $prKey;
            }
        }

        /* Localization Script Strings */
        $eventsArgs = [
            'notify_3000_05' => __("Advanced options are not available for 'Are 404s' rule", "redirect-redirection"),
            'notify_3000_06' => __("Advanced options are not available for 'All URLs' rule", "redirect-redirection"),
            'dropdown_default_message' => esc_html__("Select Option", "redirect-redirection"),
            'regex_placeholder' => __("Regex matches", "redirect-redirection"),
            'enter_the_url_placeholder' => __("Enter the URL", "redirect-redirection"),
            'enter_the_string_placeholder' => __("Enter the string", "redirect-redirection"),
            'console_error' => __("Could not find the 'data-selected-dropdown-item-id' attribute in a custom dropdown", "redirect-redirection"),
            'confirm_message' => __("This action will delete the plugin data and tables from the database and deactivate the plugin. \r\n\r\n Are you sure you want to do this?\r\n", "redirect-redirection"),
            "permalinkStructure" => $permalinkStructure,
            "permalinkRelation" => $permalinkRelation,
        ];

        $permalinkStructureExcl = [
            "dayAndName" => [],
            "monthAndName" => [],
            "postName" => [],
            "categoryAndName" => [],
            "authorAndName" => [],
        ];

        if (!empty($_GET["page"]) && trim($_GET["page"]) === self::PAGE_SETTINGS) {
            wp_register_style("ir-montserrat-css", plugins_url(IRRP_DIR_NAME . "/assets/css/assets/fonts/Montserrat/Montserrat.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-montserrat-css");

            wp_register_style("ir-normalization-css", plugins_url(IRRP_DIR_NAME . "/assets/css/assets/normalization.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-normalization-css");

            wp_register_style("ir-root-css", plugins_url(IRRP_DIR_NAME . "/assets/css/assets/root.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-root-css");

            wp_register_style("ir-app-css", plugins_url(IRRP_DIR_NAME . "/assets/css/app.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-app-css");

            wp_register_style("ir-tabs-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/tabs.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-tabs-css");

            wp_register_style("ir-header-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/header.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-header-css");

            wp_register_style("ir-filter-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/custom-filter.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-filter-css");

            wp_register_style("ir-table-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/table.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-table-css");

            wp_register_style("ir-pagination-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/pagination.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-pagination-css");

            wp_register_style("ir-btn-open-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/btn-open.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-btn-open-css");

            wp_register_style("ir-redirect-logs-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/redirect-logs.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-redirect-logs-css");

            wp_register_style("ir-redirect-content-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/redirect-content.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-redirect-content-css");

            wp_register_style("ir-redirect-table-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/redirect-table.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-redirect-table-css");

            wp_register_style("ir-delete-set-wrap-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/delete-set-wrap.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-delete-set-wrap-css");

            wp_register_style("ir-logs-actions-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/logs-actions.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-logs-actions-css");

            wp_register_style("ir-note-item-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/note-item.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-note-item-css");

            wp_register_style("ir-delete-prompt-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/delete-confirmation-prompt.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-delete-prompt-css");

            wp_register_style("ir-default-settings-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/default-settings-modal.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-default-settings-css");

            wp_register_style("ir-tooltip-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/tooltip.min.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-tooltip-css");

            wp_register_style("ir-dropdown-js-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/dropdown-with-js.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-dropdown-js-css");

            wp_register_style("ir-notification-css", plugins_url(IRRP_DIR_NAME . "/assets/css/components/notification.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-notification-css");

            wp_register_style("ir-backend-css", plugins_url(IRRP_DIR_NAME . "/assets/css/backend.css"), [], IRRP_PLUGIN_VERSION);
            wp_enqueue_style("ir-backend-css");

            wp_register_script("ir-notification-js", plugins_url(IRRP_DIR_NAME . "/assets/css/components/notification.js"), ["jquery"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-notification-js");

            // Enqueue Tooltip JS
            wp_register_script("ir-tooltip-js", plugins_url(IRRP_DIR_NAME . "/assets/js/tooltip.min.js"), ["jquery"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-tooltip-js");

            wp_register_script("ir-backend-events-js", plugins_url(IRRP_DIR_NAME . "/assets/js/backend-events.js"), ["jquery"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-backend-events-js");
            wp_localize_script("ir-backend-events-js", "irEventsJS", $eventsArgs);

            $ajaxArgs = [
                'home_url' => get_home_url(),
                'admin_url' => get_admin_url(),
                'notify_5000' => __("Please ensure your entry is valid!", "redirect-redirection"),
                'notify_home_url_used' => __("Please ensure your entry is valid! You cannot use the website home URL here!", "redirect-redirection"),
                'notify_3000_01' => __("Source URL cannot be empty!", "redirect-redirection"),
                'notify_3000_02' => __("Target URL cannot be empty!", "redirect-redirection"),
                'text_01' => __("Add this redirect", "redirect-redirection"),
                'text_02' => __("Add a redirect", "redirect-redirection"),
                'text_03' => __("Add a redirection rule", "redirect-redirection"),
                'text_04' => __("Save this redirection rule", "redirect-redirection"),
                'text_05' => __("Edit this redirection rule", "redirect-redirection"),
                'text_06' => __("Save this redirect", "redirect-redirection"),
                'text_07' => __("Edit this redirect", "redirect-redirection"),
                'text_08' => __("Add this redirection rule", "redirect-redirection"),
                'notify_3000_03' => __("Showing all logs", "redirect-redirection"),
                'notify_3000_04' => __("Showing 404s logs", "redirect-redirection"),
                "confirm_logs_delete" => __("Are you sure to delete all logs?", "redirect-redirection"),
                "type_redirection" => self::TYPE_REDIRECTION,
                "type_redirection_rule" => self::TYPE_REDIRECTION_RULE,
                "nonce" => wp_create_nonce("ir_ajax_nonce"),
            ];
            wp_register_script("ir-backend-ajax-js", plugins_url(IRRP_DIR_NAME . "/assets/js/backend-ajax.js"), ["ir-backend-events-js"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-backend-ajax-js");
            wp_localize_script("ir-backend-ajax-js", "irAjaxJS", $ajaxArgs);

            wp_register_script("ir-js-ck-js", plugins_url(IRRP_DIR_NAME . "/assets/3rd-party/ir-js-ck.min.js"), ["ir-backend-ajax-js"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-js-ck-js");
        } else if ($pagenow && $pagenow === "plugins.php") {

            wp_register_script("ir-backend-events-js", plugins_url(IRRP_DIR_NAME . "/assets/js/backend-events.js"), ["jquery"], IRRP_PLUGIN_VERSION);
            wp_enqueue_script("ir-backend-events-js");
            wp_localize_script("ir-backend-events-js", "irEventsJS", $eventsArgs);
        }
    }

    public function settingsForm() {
        include_once "html-settings.php";
    }


    public function getDataWithoutAdvancedOptions(){
        $setting = $this->getData();
        $defaultSettings = $this->getDefaultSettings();
        $setting["ignore_trailing_slashes"] = $defaultSettings["ignore_trailing_slashes"];
        $setting["ignore_parameters"] = $defaultSettings["ignore_parameters"];
        $setting["ignore_case"] = $defaultSettings["ignore_case"];
        $setting["pass_on_parameters"] = $defaultSettings["pass_on_parameters"];
        $setting["inclusion_exclusion_rules"] = $defaultSettings["inclusion_exclusion_rules"];
        $setting["rules_group1"] = $defaultSettings["rules_group1"];
        $setting["rules_group2"] = $defaultSettings["rules_group2"];
        $setting["rules_group3"] = $defaultSettings["rules_group3"];
        $setting["rules_group4"] = $defaultSettings["rules_group4"];
        $setting["rules_group5"] = $defaultSettings["rules_group5"];
        $setting["rules_group6"] = $defaultSettings["rules_group6"];
        $setting["rules_group7"] = $defaultSettings["rules_group7"];
        $setting["rules_group8"] = $defaultSettings["rules_group8"];
        return $setting;

    }

    // AJAX FUNCTIONS //

    /**
     * load tabs via ajax on click
     */
    public function loadTab() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $tab = empty($_POST["tab"]) ? "" : trim(sanitize_text_field($_POST["tab"]));

        if ($tab && in_array($tab, IrrPRedirection::$TABS)) { // tab name exists, send the data back
            $response["status"] = "success";
            $response["message"] = __("Tab content loaded", "redirect-redirection");
            ob_start();
            if ($tab === IrrPRedirection::$TABS["redirection-rules"]) {
                include_once "layouts/" . IrrPRedirection::$TABS["redirection-rules"] . ".php";
            } else if ($tab === IrrPRedirection::$TABS["redirection-and-404-logs"]) {
                include_once "layouts/" . IrrPRedirection::$TABS["redirection-and-404-logs"] . ".php";
            } else if ($tab === IrrPRedirection::$TABS["automatic-redirects"]) {
                include_once "layouts/" . IrrPRedirection::$TABS["automatic-redirects"] . ".php";
            } else if ($tab === IrrPRedirection::$TABS["change-urls"]) {
                include_once "layouts/" . IrrPRedirection::$TABS["change-urls"] . ".php";
            } else {
                include_once "layouts/" . IrrPRedirection::$TABS["specific-url-redirections"] . ".php";
            }

            $response["content"] = ob_get_clean();
            wp_send_json_success($response);
        } else { // something went wrong, send an error message
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    /**
     * load global settings
     */
    public function loadSettings() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $settingsData = get_option(self::OPTIONS_MAIN, $this->getDefaultSettings());
        if ($settingsData) { // default settings, loading...
            $response["status"] = "success";
            $response["message"] = __("Redirection data loaded", "redirect-redirection");
            ob_start();
            include_once "layouts/common/default-settings-modal.php";
            $response["content"] = ob_get_clean();
            wp_send_json_success($response);
        } else { // not found, send an error message
            $response["status"] = "error";
            $response["message"] = __("Error while loading default settings", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    /**
     * save global settings
     */
    public function saveSettings() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(stripslashes(trim($_POST["data"])), ARRAY_A));
        $data = IRRPHelper::unescapeData($data);
        $parsed = array_replace_recursive($this->getDefaultSettings(), $data);

        if ($parsed) {
            update_option(self::OPTIONS_MAIN, $parsed);
            $this->setData($parsed); // overwrite
            $response["status"] = "success";
            $response["message"] = __("Settings updated", "redirect-redirection");
            // TODO
            // ob_start();
            // include_once "layouts/common/header-settings-paragraph.php";
            // $response["content"] = ob_get_clean();
            wp_send_json_success($response);
        } else {
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function logMeWhereIFinished() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(stripslashes(trim($_POST["data"])), ARRAY_A));
        $data = IRRPHelper::unescapeData($data);
        

        $parsed = array_replace_recursive($this->getDefaultAutoRedirects(), $data);
        
        if ($parsed) {
            update_option(self::OPTIONS_AUTO_REDIRECTS, $parsed);
            $response["status"] = "success";
            $response["message"] = __("Settings updated", "redirect-redirection");
            wp_send_json_success($response);
        } else {
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    public function logRequests(){
        return get_option(self::OPTIONS_LOGS_STATUS) == "1";
    }

}
