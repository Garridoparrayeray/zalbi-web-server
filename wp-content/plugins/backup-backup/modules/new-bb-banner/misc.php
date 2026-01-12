<?php

  /**
   * File for handling new BB banner
   *
   * @category Child Plugin
   * @version v0.1.0
   * @since v0.1.0
   * @author iClyde <kontakt@iclyde.pl>
   */

  // Namespace
  namespace Inisev\Subs;

  // Disallow direct access
  if (defined('ABSPATH')) {

    /**
     * Main class for handling new BB banner
     */
    if (!class_exists('Inisev\Subs\New_BB_Banner')) {
      class New_BB_Banner {

        /**
         * Local variables
         */
        private $root; // __ROOT__ of plugin's root
        private $file; // __FILE__ of plugin's root
        private $slug; // Plugin's slug
        private $is_bmi_exists = false; // BMI plugin exists
        private $name;

        /**
         * Local URLs
         */
        private $root_url; // Root URL for plugin's dir
        private $assets_url; // Root URL for banner assets
        private $plugin_menu_url; // Plugin's settings menu
        public $option_name = '_new_bb_banner'; // Option name for this module
        public $using_since; // Check since user uses this plugin


        /**
         * __construct:
         * Compile some variables for "future use"
         * Such as slug of current plugin, root dir of plugin
         *
         * @param  string $root_file       __FILE__ of plugin's main file
         * @param  string $root_dir        __DIR__ of plugin's main file
         * @param  string $individual_slug Individual slug - mostly plugin's slug
         * @param  string $display_name    The name that will be displayed in the banner
         * @param  string $plugin_menu_url Plugin menu slug example.com/wp-admin/admin.php?page=<this slug here>
         */
        function __construct($root_file, $root_dir, $individual_slug, $display_name, $plugin_menu_url) {

          $this->file = $root_file;
          $this->root = $root_dir;
          $this->slug = $individual_slug;
          $this->name = $display_name;

          $this->plugin_menu_url = admin_url('admin.php?page=' . $plugin_menu_url);

          $this->root_url = plugin_dir_url($this->file);
          $this->assets_url = $this->root_url . 'modules/new-bb-banner/assets/';
          $this->is_bmi_exists = is_dir(WP_PLUGIN_DIR . '/backup-backup') || is_dir(WP_PLUGIN_DIR . '/backup-backup-pro');


          $time = time();
          $option = get_option($this->option_name, [
            'dismissed' => false,
            'dismissed_at' => 0,
            'using_since' => $time
          ]);

          if ($option['using_since'] == $time) {
            update_option($this->option_name, $option);
          } 

          $this->using_since = $option['using_since'];


          // Add handler for Ajax request
          if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {

            // Check if slug is defined
            if (isset($_POST['token']) && $_POST['token'] === 'new_bb_banner') {

              // Handle the request
              add_action('wp_ajax_dismiss_new_bb_banner', [&$this, 'dismiss_banner']);
            }

            // Stop for POST
            return;

          }

          add_action('wp_loaded', [&$this, 'init_new_bb_banner']);

        }

        /**
         * __asset - Loads assets
         *
         * @param  string $file path relative
         * @return string       file URL
         */
        private function __asset($file) {

          return $this->assets_url . $file;

        }

        /**
         * __dir_asset - Loads assets
         *
         * @param  string $file path relative
         * @return string       absolute path
         */
        private function __dir_asset($file) {

          return __DIR__ . '/assets' . '/' . $file;

        }

        /**
         * _asset - Loads assets and automatically echo
         *
         * @param  string $file path relative
         * @echo   string       file URL
         */
        private function _asset($file) {

          echo $this->assets_url . $file;

        }

        /**
         * _dir_asset - Loads assets and automatically echo
         *
         * @param  string $file path relative
         * @echo   string       absolute path
         */
        private function _dir_asset($file) {

          echo __DIR__ . '/assets' . '/' . $file;

        }

        /**
         * can_be_displayed - check if the banner should be displayed
         *
         * @return bool true if banner can be displayed
         */
        private function can_be_displayed() {      

          $option = get_option($this->option_name, [
            'dismissed' => false,
            'dismissed_at' => 0
          ]);

          if ($option['dismissed'] === true) {
            return false;
          }

          $site_url = get_site_url();
          $site_url = str_replace('http://', '', $site_url);
          $site_url = str_replace('https://', '', $site_url);
          $site_url = str_replace('www.', '', $site_url);

          // IF site match regex then display
          if (preg_match('/^[a-c]/i', $site_url)) {
            return true;
          }

          if ($this->slug !== 'backup-backup'){
            // WILL BE USED IN OTHER PLUGINS
          }

          return false;
        }

        /**
         * add_assets - adds required assests by the banner
         *
         * @return void
         */
        public function add_assets() {

          wp_enqueue_script('new-bb-banner-script', $this->__asset('js/script.js'), [], filemtime($this->__dir_asset('js/script.js')), true);
          wp_enqueue_style('new-bb-banner-style', $this->__asset('css/style.css'), [], filemtime($this->__dir_asset('css/style.css')));
          wp_localize_script('new-bb-banner-script', 'new_bb_banner', [
            'dismiss_nonce' => wp_create_nonce('new_bb_banner_dismiss'),
            'is_backup_pro_exists' => is_dir(WP_PLUGIN_DIR . '/backup-backup-pro'),
            // 'is_backup_pro_exists' => false, // For Testing Purpose
            'current_plugin' => $this->slug, // Will be used for future use
            'is_bmi_exists' => $this->is_bmi_exists
          ]);

        }

        /**
         * display_banner - loads the HTML and prints it in the header only once
         *
         * @return void
         */
        public function display_banner() {

          if (!defined('NEW_BB_BANNER_H_HTML_LOADED')) {
            define('NEW_BB_BANNER_H_HTML_LOADED', true);
            include_once __DIR__ . '/views/banner.php';
          }

        }

        /**
         * dismiss_banner - Handles all POST actions
         *
         * @param  string $_POST['slug'] - the unique slug
         * @param  string $_POST['mode'] - the unique action remind/dismiss
         *
         * @return void returns JSON response to browser
         */
        public function dismiss_banner() {
          if (check_ajax_referer('new_bb_banner_dismiss', 'nonce', false) === false) {
            wp_send_json_error();
          }

          if (isset($_POST['shouldRedirectToBMI'])) $shouldRedirectToBMI = sanitize_text_field($_POST['shouldRedirectToBMI']);
          else $shouldRedirectToBMI = false;

          update_option($this->option_name, [
            'dismissed' => true,
            'dismissed_at' => time(),
            'using_since' => $this->using_since
          ]);

          if ($shouldRedirectToBMI == 'true') {
            wp_send_json_success([
              'redirect' => $this->plugin_menu_url
            ]);
          } else {
            wp_send_json_success();
          }
        }

        /**
         * init_new_bb_banner - initialization when the user is authenticated already
         *
         * @return void
         */
        public function init_new_bb_banner() {

          if ($this->can_be_displayed()) {
            add_action('admin_enqueue_scripts', [&$this, 'add_assets']);
            add_action('admin_notices', [&$this, 'display_banner']);
          }

        }

      }
    }

  }
