<?php

  // Namespace
  namespace BMI\Plugin;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Require classes
  require_once BMI_INCLUDES . '/logger.php';

  // Alias for classes
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\CRON\BMI_Crons as Crons;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
  use BMI\Plugin\Heart\BMI_Backup_Heart as Bypasser;
  use BMI\Plugin\Zipper\BMI_Zipper as Zipper;
  use BMI\Plugin\Staging\BMI_Staging as Staging;
  use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;

  // Uninstallator
  if (!function_exists('bmi_uninstall_handler')) {
    function bmi_uninstall_handler() {
      require_once BMI_ROOT_DIR . '/uninstall.php';
    }
  }

  /**
   * Backup Migration Main Class
   */
  class Backup_Migration_Plugin {
    public function initialize() {

      // Determine which BMI version is used
      add_action('wp_head', function () {
        echo '<meta name="bmi-version" content="' . BMI_VERSION . '" />';
      });

      if (!file_exists(BMI_BACKUPS)) @mkdir(BMI_BACKUPS, 0755, true);
      if (!file_exists(BMI_STAGING)) @mkdir(BMI_STAGING, 0755, true);

      // Handle PHP CLI functions
      if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {

        // Below all WordPress functions and directives can be accessed
        if (defined('BMI_CLI_FUNCTION')) {

          if (BMI_CLI_FUNCTION == 'bmi_restore' && defined('BMI_CLI_ARGUMENT')) {

            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            $_POST['f'] = 'restore-backup';
            if (defined('BMI_CLI_ARGUMENT_2')) {
              $_POST['remote'] = BMI_CLI_ARGUMENT_2;
            } else $_POST['remote'] = false;
            $_POST['file'] = BMI_CLI_ARGUMENT;

            $this->ajax(true);

          } elseif (BMI_CLI_FUNCTION == 'bmi_backup' || BMI_CLI_FUNCTION == 'bmi_backup_cron') {
            
            if (BMI_CLI_FUNCTION == 'bmi_backup_cron') {
              define('BMI_DOING_SCHEDULED_BACKUP', true);
              define('BMI_DOING_SCHEDULED_BACKUP_VIA_CLI', true);
            }
            
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            $_POST['f'] = 'create-backup';

            $this->ajax(true);

          } elseif (BMI_CLI_FUNCTION == 'bmi_quick_migration') {

            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            $_POST['f'] = 'download-backup';
            $_POST['url'] = BMI_CLI_ARGUMENT;

            $this->ajax(true);

          }

        }

        return;

      }

      if (defined('BMI_RESTORE_SECRET') && defined('BMI_POST_CONTINUE_RESTORE') && constant('BMI_POST_CONTINUE_RESTORE') === true) {
        
        if (!isset($_POST['bmi_restore_secret'])) exit;
        
        // Check the secret
        $bmi_secret_storage = BMI_TMP . DIRECTORY_SEPARATOR . '.restore_secret';
        if (file_exists($bmi_secret_storage)) {
          $bmi_saved_secret = file_get_contents($bmi_secret_storage);
          if ($bmi_saved_secret === $_POST['bmi_restore_secret']) {
            $bmi_continue_module = true;
          } else exit;
        } else exit;

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $_POST['f'] = 'continue_restore_process';

        $this->ajax(true);

        return;

      }

      // Hooks
      register_deactivation_hook(BMI_ROOT_FILE, [&$this, 'deactivation']);
      register_uninstall_hook(BMI_ROOT_FILE, 'bmi_uninstall_handler');

      // File downloading
      add_action('wp_loaded', [&$this, 'handle_downloading']);

      // Additional actions
      if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        add_action('wp_loaded', [&$this, 'handle_after_actions'], 1000);
      }
      // Handle CRONs
      add_action('bmi_do_backup_right_now', [&$this, 'handle_cron_backup']);
      add_action('bmi_handle_cron_check', [&$this, 'handle_cron_check']);
      add_action('wp_loaded', [&$this, 'handle_crons']);
      add_action('wp_loaded', [&$this, 'include_offline']);
      add_action('admin_notices', [&$this, 'incompatibility_notices']);
      
      // Return if CRON time
      if (function_exists('wp_doing_cron') && wp_doing_cron()) return;

      // Check user permissions
      $user = get_userdata(get_current_user_id());
      if (!$user || !$user->roles) return;
      if (!current_user_can('do_backups') && !in_array('administrator', (array) $user->roles)) return;

      if (Dashboard\bmi_get_config('OTHER:PROMOTIONAL:DISPLAY') != 'true') {
        
        // Include our cool banner
        include_once BMI_INCLUDES . '/banner/misc.php';

        // Review banner
        if (!is_dir(WP_PLUGIN_DIR . '/backup-backup-pro')) {
          if (!(class_exists('\Inisev\Subs\Inisev_Review') || class_exists('Inisev\Subs\Inisev_Review') || class_exists('Inisev_Review'))) {
            require_once BMI_MODULES_DIR . 'review' . DIRECTORY_SEPARATOR . 'review.php';
          }
          $review_banner = new \Inisev\Subs\Inisev_Review(BMI_ROOT_FILE, BMI_ROOT_DIR, 'backup-backup', 'Backup & Migration', 'http://bit.ly/3vdk45L', 'backup-migration');
        }

        if (!(class_exists('\Inisev\Subs\New_BB_Banner') || class_exists('Inisev\Subs\New_BB_Banner') || class_exists('New_BB_Banner'))) {
          require_once BMI_MODULES_DIR . 'new-bb-banner' . DIRECTORY_SEPARATOR . 'misc.php';
          new \Inisev\Subs\New_BB_Banner(BMI_ROOT_FILE, BMI_ROOT_DIR, 'backup-backup', 'Backup & Migration', 'backup-migration');
        }


        // GDrive banner
        if (!is_dir(WP_PLUGIN_DIR . '/backup-backup-pro')) {
          if (!(class_exists('\Inisev\Subs\BMI_Banners_GDrive') || class_exists('Inisev\Subs\BMI_Banners_GDrive') || class_exists('BMI_Banners_GDrive'))) {
            require_once BMI_MODULES_DIR . 'gdrivebanner' . DIRECTORY_SEPARATOR . 'misc.php';
          }
          $gdirve_banner = new \Inisev\Subs\BMI_Banners_GDrive('Backup & Migration', 'backup-migration');
        }
        
      }

      // POST Logic
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Register AJAX Handler
        add_action('wp_ajax_backup_migration', [&$this, 'ajax']);

        // Stop GET Registration
        // return; // Commented because of conflicts with USM Icons

      }

      // Actions
      add_action('admin_init', [&$this, 'admin_init_hook']);
      add_action('admin_menu', [&$this, 'submenu']);
      add_action('admin_notices', [&$this, 'admin_notices']);


      // Settings action
      add_filter('plugin_action_links_' . plugin_basename(BMI_ROOT_FILE), [&$this, 'settings_action']);

      // Whitelist configuration files for Security Ninja
      add_filter('securityninja_whitelist', [&$this, 'securityninja_whitelist_config_files']);

      // Ignore below actions if those true
      if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        return;
      }

      // Styles & scripts
      add_action('admin_enqueue_scripts', [&$this, 'enqueue_styles']);
      add_action('admin_enqueue_scripts', [&$this, 'enqueue_scripts']);

      // External storage errors
      add_action('bmi_external_errors', function() {

        if (file_exists(BMI_INCLUDES . 'notices/dropbox-issues-notice.php'))
          require_once BMI_INCLUDES . 'notices/dropbox-issues-notice.php';

        if (file_exists(BMI_INCLUDES . '/notices/aws-issues.php'))
          require_once BMI_INCLUDES . '/notices/aws-issues.php';

        if (file_exists(BMI_INCLUDES . '/notices/google-drive-issues.php'))
          require_once BMI_INCLUDES . '/notices/google-drive-issues.php';

        if (file_exists(BMI_INCLUDES . '/notices/wasabi-issues.php'))
          require_once BMI_INCLUDES . '/notices/wasabi-issues.php';
        
        require_once BMI_INCLUDES . '/notices/backupbliss.php';
      });

      $upload_issue_notice = $this->backupbliss_space_issues();

      if ($upload_issue_notice) {
        add_action("admin_notices", function() use ($upload_issue_notice) {
          $global_warning = true;
          $error_message = $upload_issue_notice;
          include BMI_INCLUDES . '/dashboard/modals/bb-warning-notice.php';
        });
      }

    }
    
    public function incompatibility_notices() {
      
      if (strpos(home_url(), 'instawp') === false && strpos(home_url(), 'playground.wordpress') === false) return;
      
      $environment = 'sandbox';
      if (strpos(home_url(), 'instawp') !== false) $environment = 'InstaWP';
      if (strpos(home_url(), 'playground.wordpress') !== false) $environment = 'WordPress Playground';
      
      $class = 'notice notice-warning';
      $message = __('We noticed that you are using %s, our plugin may not work in this environment, please use %s environment instead.', 'backup-backup');
      
      printf('<div class="%s"><p><b>Backup Migration:</b> %s</p></div>', 
        esc_attr($class), 
        sprintf(
          esc_html($message), 
          '<i>' . $environment . '</i>', 
          '<a href="https://tastewp.com" target="_blank">TasteWP</a>'
        )
      );
    }

    public static function randomString($max = 16) {

      $bank = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $bank .= 'abcdefghijklmnopqrstuvwxyz';
      $bank .= '0123456789';

      $str = str_shuffle($bank);

      while (is_numeric($str[0])) {
        $str = str_shuffle($bank);
      }

      $str = substr($str, 0, $max);

      return $str;

    }

    public static function isFunctionEnabled($func) {

      $disabled = explode(',', ini_get('disable_functions'));
      $isDisabled = in_array($func, $disabled);
      if (!$isDisabled && function_exists($func)) return true;
      else return false;

    }

    /**
     * hotFixPatches - Function which fixes things for "old" users
     *
     * @return void
     */
    public function hotfix_patches() {

      if (!is_admin()) return;

      $current_patch = get_option('bmi_hotfixes', array());
      if (!in_array('BMI_D20_M07_01', $current_patch)) {

        $current_directory = Dashboard\bmi_get_config('STORAGE::LOCAL::PATH');
        if (basename($current_directory) == 'backup-migration') {

          require_once BMI_INCLUDES . '/ajax.php';
          $handler_a = new BMI_Ajax(true);

          $handler_a->post['directory'] = dirname($current_directory) . DIRECTORY_SEPARATOR . 'backup-migration-' . $this->randomString(10);
          $handler_a->post['access'] = Dashboard\bmi_get_config('STORAGE::DIRECT::URL');

          $res_a = $handler_a->saveStorageConfig();
          if (isset($res_a['status']) && $res_a['status'] == 'success') {

            $current_patch[] = 'BMI_D20_M07_01';

          }

        } else {

          $current_patch[] = 'BMI_D20_M07_01';

        }

      }

      if (!in_array('BMI_D17_M12_Y21_02', $current_patch)) {

        $current_splitting_value = Dashboard\bmi_get_config('OTHER:RESTORE:SPLITTING');
        $current_query_size = Dashboard\bmi_get_config('OTHER:DB:QUERIES');

        $current_query_size = intval($current_query_size);
        if ($current_splitting_value == 'true' || $current_splitting_value === true) {
          $current_splitting_value = true;
        } else {
          $current_splitting_value = false;
        }

        if ($current_splitting_value === false || $current_query_size != 300) {

          $b_db_restore_splitting = true;
          $b_db_query_size = '2000';

          $error_b = 0;
          if (!Dashboard\bmi_set_config('OTHER:RESTORE:SPLITTING', $b_db_restore_splitting)) {
            $error_b++;
          }
          if (!Dashboard\bmi_set_config('OTHER:DB:QUERIES', $b_db_query_size)) {
            $error_b++;
          }

          if ($error_b <= 0) {

            $current_patch[] = 'BMI_D17_M12_Y21_02';

          }

        } else {

          $current_patch[] = 'BMI_D17_M12_Y21_02';

        }

      }
      
      if (!in_array('BMI_D13_M08_Y23_01', $current_patch)) {

        $current_direct_download_value = Dashboard\bmi_get_config('OTHER:DOWNLOAD:DIRECT');
        
        if ($current_direct_download_value === false || $current_direct_download_value == 'false') {

          $error_b = 0;
          if (!Dashboard\bmi_set_config('OTHER:DOWNLOAD:DIRECT', true)) $error_b++;
          if ($error_b <= 0) $current_patch[] = 'BMI_D13_M08_Y23_01';

        } else $current_patch[] = 'BMI_D13_M08_Y23_01';

      }

      update_option('bmi_hotfixes', $current_patch);

    }

    public function ajax($cli = false) {
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        if ((isset($_POST['token']) && $_POST['token'] == 'bmi' && isset($_POST['f']) && is_admin()) || $cli) {
          try {
            
            if (gettype($cli) != 'boolean') $cli = false;

            // Extend execution time
            if ($this->isFunctionEnabled('headers_sent') && $this->isFunctionEnabled('session_status')) {
              if (!headers_sent() && session_status() === PHP_SESSION_DISABLED) {
                if ($this->isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
                if ($this->isFunctionEnabled('set_time_limit')) @set_time_limit(16000);
                if ($this->isFunctionEnabled('ini_set')) {
                  @ini_set('max_execution_time', '259200');
                  @ini_set('max_input_time', '259200');
                }
              }
            }

            // May cause issues with auto login
            // if (strlen(session_id()) > 0) session_write_close();

            if ($this->isFunctionEnabled('register_shutdown_function')) {
              register_shutdown_function([$this, 'execution_shutdown']);
            }
            
            // Require AJAX Handler
            require_once BMI_INCLUDES . '/ajax.php';
            $handler = new BMI_Ajax($cli);

          } catch (\Exception $e) {

            Logger::error('POST error:');
            Logger::error($e);
            if ($_POST['f'] == 'create-backup') {
              $progress = &$GLOBALS['bmi_backup_progress'];
              $this->handleErrorDuringBackup($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }
            if ($_POST['f'] == 'restore-backup') {
              $progress = &$GLOBALS['bmi_migration_progress'];
              $this->handleErrorDuringRestore($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }

            $this->res(['status' => 'error', 'error' => $e]);
            exit;

          } catch (\Throwable $e) {

            Logger::error('POST error:');
            Logger::error($e);
            if ($_POST['f'] == 'create-backup') {
              $progress = &$GLOBALS['bmi_backup_progress'];
              $this->handleErrorDuringBackup($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }
              if ($_POST['f'] == 'restore-backup') {
              $progress = &$GLOBALS['bmi_migration_progress'];
              $this->handleErrorDuringRestore($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }

            $this->res(['status' => 'error', 'error' => $e]);
            exit;

          }
        }
      }
    }

    public function execution_shutdown() {
      $err = error_get_last();

      if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {
        $lock_cli = BMI_BACKUPS . '/.migration_lock_cli';
        $lock_cli_end = BMI_BACKUPS . '/.migration_lock_ended';
        $cli_failed_lock = BMI_BACKUPS . '/.backup_lock_cli_failed';
        $lock_cli_end_backup = BMI_BACKUPS . '/.backup_lock_cli_end';
        
        if (file_exists($lock_cli)) @unlink($lock_cli);
        if (file_exists($lock_cli_end)) @touch($lock_cli_end);
        if (file_exists($cli_failed_lock)) @touch($cli_failed_lock);
        if (file_exists($lock_cli_end_backup)) @touch($lock_cli_end_backup);
      }

      if ($err != null) {

        $msg = $err['message'];
        $file = $err['file'];
        $line = $err['line'];
        $type = $err['type'];

        if ($type != '1' && ($type != E_ERROR && $type != E_CORE_ERROR && $type != E_COMPILE_ERROR && $type != E_USER_ERROR && $type != E_RECOVERABLE_ERROR)) {
          Logger::error(__('There was an error before request shutdown (but it was not logged to backup/restore log)', 'backup-backup'));
          Logger::error(__('Error message: ', 'backup-backup') . $msg);
          Logger::error(__('Error file/line: ', 'backup-backup') . $file . '|' . $line);
          Logger::error(__('Error handler: ', 'backup-backup') . 'init#01' . '|' . $type);
          return;
        }

        if (isset($GLOBALS['bmi_error_handled']) && $GLOBALS['bmi_error_handled']) return;
        if ($_POST['f'] == 'create-backup') {
          Logger::error(__('There was an error during backup', 'backup-backup'));
          Logger::error(__('Error message: ', 'backup-backup') . $msg);
          Logger::error(__('Error file/line: ', 'backup-backup') . $file . '|' . $line);
          Logger::error(__('Error handler: ', 'backup-backup') . 'init#02' . '|' . $type);
          $progress = &$GLOBALS['bmi_backup_progress'];
          if ($progress) {
            $progress->log(__('Error message: ', 'backup-backup') . $msg, 'error');
            $progress->log(__('You can get more pieces of information in troubleshooting log file.', 'backup-backup'), 'error');
          }
          $this->handleErrorDuringBackup($msg, $file, $line, $progress);

          $fullPath = BMI_TMP . DIRECTORY_SEPARATOR;
          array_map('unlink', glob($fullPath . '*.tmp'));
          array_map('unlink', glob($fullPath . '*.gz'));
        }

        if ($_POST['f'] == 'restore-backup') {
          Logger::error(__('There was an error during restore process', 'backup-backup'));
          Logger::error(__('Error message: ', 'backup-backup') . $msg);
          Logger::error(__('Error file/line: ', 'backup-backup') . $file . '|' . $line);
          Logger::error(__('Error handler: ', 'backup-backup') . 'init#03' . '|' . $type);
          $progress = &$GLOBALS['bmi_migration_progress'];
          if ($progress) {
            $progress->log(__('Error message: ', 'backup-backup') . $msg, 'error');
            $progress->log(__('You can get more pieces of information in troubleshooting log file.', 'backup-backup'), 'error');
          }
          $this->handleErrorDuringRestore($msg, $file, $line, $progress);
        }

        $this->res(['status' => 'error', 'error' => $err]);
        exit;
      }
    }

    public function handleErrorDuringBackup($msg, $file, $line, &$progress) {
      $backup = $GLOBALS['bmi_current_backup_name'];

      Logger::log('Due to fatal error backup handled correctly (closed and removed).');
      if ($progress) {
        $progress->log(__('Something bad happened on PHP side.', 'backup-backup'), 'error');
        $progress->log(__('Unfortunately we had to remove the backup (if partly created).', 'backup-backup'), 'error');
        $progress->log(__('Error message: ', 'backup-backup') . $msg, 'error');
        $progress->log(__('Error file/line: ', 'backup-backup') . $file . '|' . $line, 'error');
        if (strpos($msg, 'execution time') !== false) {
          $progress->log(__('Probably we could not increase the execution time, please edit your php.ini manually', 'backup-backup'), 'error');
        }
      }

      $backup_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backup;
      $partial_backup_path = glob( BMI_BACKUPS . DIRECTORY_SEPARATOR . $backup . '.??????');
      if (is_array($partial_backup_path) && count($partial_backup_path) > 0) {
        foreach ($partial_backup_path as $key => $value) {
          @unlink($value);
        }
      }
      if (file_exists($backup_path)) @unlink($backup_path);
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running');
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort');

      if ($progress) {
        $progress->log(__("Aborting backup...", 'backup-backup'), 'step');
        $progress->log('#002', 'END-CODE');
        $progress->end();
      }
    }

    public function handleErrorDuringRestore($msg, $file, $line, &$progress) {
      Logger::log('There was fatal error during restore.');
      if ($progress) {
        $progress->log(__('Something bad happened on PHP side.', 'backup-backup'), 'error');
        $progress->log(__('Error message: ', 'backup-backup') . $msg, 'error');
        $progress->log(__('Error file/line: ', 'backup-backup') . $file . '|' . $line, 'error');
      }
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock');
      if ($progress) {
        $progress->log(__("Aborting & unlocking restore process...", 'backup-backup'), 'step');
        $progress->end();
      }

      $lock = BMI_BACKUPS . '/.migration_lock';
      if (file_exists($lock)) @unlink($lock);
    }

    public function submenu() {

      // Menu icon
      $icon_url = $this->get_asset('images', 'logo-min.png');

      // Main menu slug
      $parentSlug = 'backup-migration';

      // Content
      $content = [$this, 'settings_page'];

      // Main menu hook
      add_menu_page('Backup Migration', '<span id="bmi-menu">Backup Migration</span>', 'read', $parentSlug, $content, $icon_url, 98);

      // Remove default submenu by menu
      remove_submenu_page($parentSlug, $parentSlug);

    }

    public function settings_action($links) {
      $text = __('Manage', 'backup-backup');
      $links['bmi-settings-link'] = '<a href="' . admin_url('/admin.php?page=backup-migration') . '">' . $text . '</a>';

      return $links;
    }

    /**
     * Whitelist configuration files for Security Ninja plugin.
     *
     * This filter callback prevents Security Ninja from deleting or flagging
     * Backup Migration configuration files as suspicious. These files are
     * essential for the plugin's operation and should be preserved.
     *
     * @param array $whitelist Existing whitelist array from Security Ninja
     * @return array Modified whitelist array with our configuration files added
     */
    public function securityninja_whitelist_config_files($whitelist = array()) {
      if (!is_array($whitelist)) {
        $whitelist = array();
      }

      // Add all configuration files to the whitelist
      $config_files = array(
        BMI_CONFIG_DEFAULT,
        BMI_STATIC_PHP_CONFIG,
        BMI_INCLUDES . DIRECTORY_SEPARATOR . 'htaccess' . DIRECTORY_SEPARATOR . '*'
      );

      // Add config directory if defined
      if (defined('BMI_CONFIG_DIR') && BMI_CONFIG_DIR) {
        $config_files[] = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . '*';
      }

      // Add config path if defined
      if (defined('BMI_CONFIG_PATH') && BMI_CONFIG_PATH) {
        $config_files[] = BMI_CONFIG_PATH;
      }

      // Merge with existing whitelist and remove duplicates
      $whitelist = array_unique(array_merge($whitelist, $config_files));

      return $whitelist;
    }

    public function include_offline() {

      
        // Handle offline tasks
        if (!class_exists('BMI_Offline')) {
          
          if (file_exists(BMI_INCLUDES . '/offline.php')) {
            require_once BMI_INCLUDES . '/offline.php';
            new BMI_Offline();
          }
        }

    }

    public function settings_page() {

      // Set email if does not exist
      if (!Dashboard\bmi_get_config('OTHER:EMAIL')) {
        Dashboard\bmi_set_config('OTHER:EMAIL', get_bloginfo('admin_email'));
      }

      // Require The HTML
      require_once BMI_INCLUDES . '/dashboard/settings.php';
    }

    public function backupbliss_space_issues() {
      require_once BMI_INCLUDES . '/external/backupbliss.php';
      $backupbliss = new BackupBliss();
      $upload_issue_notice = false;
      if ($backupbliss->canShowFailureWarnNotice()) {
        $upload_issue_notice = $backupbliss->getNotice("upload_issue_space");
      }
      return $upload_issue_notice;
    }

    public function admin_init_hook() {
      $this->hotfix_patches();
      if (get_option('_bmi_redirect', false)) {
        $this->fixLitespeed();
        delete_option('_bmi_redirect');
        wp_safe_redirect(admin_url('admin.php?page=backup-migration'));
      }
    }

    public function admin_notices() {
      if (get_current_screen()->id != 'toplevel_page_backup-migration' && get_option('bmi_display_email_issues', false)) {
        ?>
        <div class="notice notice-warning">
          <p>
            <?php _e('There was an error during automated backup, please', 'backup-backup'); ?>
            <?php echo '<a href="' . admin_url('/admin.php?page=backup-migration') . '">' . __('check that.', 'backup-backup') . '</a>'; ?>
          </p>
        </div>
        <?php
      }
    }

    public function handle_crons() {
      if (Dashboard\bmi_get_config('CRON:ENABLED') !== true) return;

      $time = get_option('bmi_backup_check', 0);
      if ((time() - $time) > 60) {
        update_option('bmi_backup_check', time());

        do_action('bmi_handle_cron_check');
      }
    }

    /**
     * Retrieves a list of active security plugins detected on the site.
     *
     * This function checks the list of currently active plugins and identifies
     * common security plugins by their slugs. It returns a key-value array 
     * where keys are plugin slugs and values are their human-readable names.
     *
     * Supported plugins:
     * - Wordfence
     * - Sucuri Security
     *
     * @return array<string, string> Associative array of detected security plugins.
     *                                Format: [ 'plugin_slug' => 'Plugin Name' ]
     */
    public static function get_active_security_plugins() {
      $active_plugins = [];
      $plugins = get_option('active_plugins', []);
      if (is_array($plugins) && count($plugins) > 0) {
        foreach ($plugins as $plugin) {
          if (strpos($plugin, 'wordfence') !== false) {
            $active_plugins['wordfence'] = 'Wordfence';
          } elseif (strpos($plugin, 'security-ninja') !== false) {
            $active_plugins['security-ninja'] = 'Security Ninja';
          }
        }
      }
      return $active_plugins;
    }

    public static function email_error($msg) {
      Logger::log('Displaying some issues about email sending...');
      update_option('bmi_display_email_issues', $msg);
    }

    public function backup_inproper_time($should_time) {
      $plan_file = BMI_TMP . DIRECTORY_SEPARATOR . '.plan';
      if (!file_exists($plan_file) || intval($should_time) < 1234567890) return;

      $currentDate = date('Y-m-d');
      if (get_option('bmi_last_email_notification', false) == $currentDate) {
        return;
      }

      Logger::log('Sending notification about backup being late');
      $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
      $subject = Dashboard\bmi_get_config('OTHER:EMAIL:TITLE');
      $message = __("Automatic backup was not on time because there was no traffic on the site.", 'backup-backup') . "\n";
      $message .= __("Backup was made on: ", 'backup-backup') . date('Y-m-d H:i:s') . __(', but should be on: ', 'backup-backup') . date('Y-m-d H:i:s', $should_time);
      $message .= ' ' . __("(server time)", 'backup-backup');

      Logger::debug($message);
      if (!self::send_notification_mail($email, $subject, $message)) {
        $issue = __("Couldn't send mail to you, please check server configuration.", 'backup-backup') . '<br>';
        $issue .= '<b>' . __("Message you missed because of this: ", 'backup-backup') . '</b>' . $message;
        self::email_error($issue);
      }
    }

    public function handle_cron_check() {

      if (Dashboard\bmi_get_config('CRON:ENABLED') !== true) return;

      $now = time();
      if (file_exists(BMI_TMP . DIRECTORY_SEPARATOR . '.last')) {
        $last = @file_get_contents(BMI_TMP . DIRECTORY_SEPARATOR . '.last');
        $last_status = explode('.', $last)[0];
        $last_time = intval(explode('.', $last)[1]);
      } else {
        $last_time = 0;
        $last_status = 0;
      }

      if (file_exists(BMI_TMP . DIRECTORY_SEPARATOR . '.plan')) {
        $plan = intval(@file_get_contents(BMI_TMP . DIRECTORY_SEPARATOR . '.plan'));
        if ($last_time < $plan && ((time() - $plan) > 7200)) {
          if ($last_status !== '0') {
            if (!wp_next_scheduled('bmi_do_backup_right_now')) {
              wp_schedule_single_event(time(), 'bmi_do_backup_right_now');
            }
          }
        }
      }

    }

    public function get_next_cron($curr = false) {
      if ($curr === false) {
        $curr = time();
      }

      $time = Crons::calculate_date([
        'type' => Dashboard\bmi_get_config('CRON:TYPE'),
        'week' => Dashboard\bmi_get_config('CRON:WEEK'),
        'day' => Dashboard\bmi_get_config('CRON:DAY'),
        'hour' => Dashboard\bmi_get_config('CRON:HOUR'),
        'minute' => Dashboard\bmi_get_config('CRON:MINUTE')
      ], $curr);

      return $time;
    }

    public function handle_cron_error($e) {
      Logger::error(__("Automatic backup failed at time: ", 'backup-backup') . date('Y-m-d, H:i:s'));
      if (is_object($e) || is_array($e)) {
        Logger::error('Error: ' . $e->getMessage());
      } else {
        Logger::error('Error: ' . $e);
      }

      $notis = Dashboard\bmi_get_config('OTHER:EMAIL:NOTIS');
      if (in_array($notis, [true, 'true'])) {
        $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
        $subject = Dashboard\bmi_get_config('OTHER:EMAIL:TITLE');
        $message = __("There was an error during automatic backup, please check the logs.", 'backup-backup');
        if (is_string($e)) {
          $message .= "\nError: " . $e;
        }

        if (!self::send_notification_mail($email, $subject, $message, true)) {
          $issue = __("Couldn't send mail to you, please check server configuration.", 'backup-backup') . '<br>';
          $issue .= '<b>' . __("Message you missed because of this: ", 'backup-backup') . '</b>' . $message;
          self::email_error($issue);
        }
      }

      if (file_exists(BMI_BACKUPS . '/.cron')) {
        @unlink(BMI_BACKUPS . '/.cron');
      }
    }

    public static function send_notification_mail($email, $subject, $message, $force = false) {

      $currentDate = date('Y-m-d');
      if (get_option('bmi_last_email_notification', false) == $currentDate && $force === false) {
        Logger::log(__("Disallowing to send mail as today we already sent one.", 'backup-backup'));
        return;
      }

      update_option('bmi_last_email_notification', $currentDate);

      $email_fail = __("Could not send the email notification about that fail", 'backup-backup');

      try {

        if (wp_mail($email, $subject, $message)) {
          Logger::log(__("Sent email notification to: ", 'backup-backup') . $email);

          return true;
        } else {
          Logger::error($email_fail);
          self::email_error(__("Couldn't send notification via email, please check the email and your server settings.", 'backup-backup'));

          return false;
        }

      } catch (\Exception $e) {
        Logger::error($email_fail);
        self::email_error(__("Couldn't send notification via email due to error, please check plugin logs for more details.", 'backup-backup'));

        return false;
      } catch (\Throwable $e) {
        Logger::error($email_fail);
        self::email_error(__("Couldn't send notification via email due to error, please check plugin logs for more details.", 'backup-backup'));

        return false;
      }
    }

    public static function handle_after_cron() {
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
      $backups = new Backups();
      $availableBackups = $backups->getAvailableBackups();
      $list = $availableBackups['local'];

      $cron_list = [];
      $cron_dates = [];
      $sortedMD5s = [];
      foreach ($list as $key => $value) {
        if ($list[$key][6] == true) {
          if ($list[$key][5] == 'unlocked') {
            $cron_list[$list[$key][1]] = $list[$key][0];
            $cron_dates[] = $list[$key][1];
            $sortedMD5s[] = [$list[$key][1], $list[$key][7]];
          }
        }
      }

      usort($cron_dates, function ($a, $b) {
        return (strtotime($a) < strtotime($b)) ? -1 : 1;
      });

      $cron_dates = array_slice($cron_dates, 0, -(intval(Dashboard\bmi_get_config('CRON:KEEP'))));
      foreach ($cron_dates as $key => $value) {
        $name = $cron_list[$cron_dates[$key]];
        $name = explode('#%&', $name)[1];
        Logger::log(__("Removing backup due to keep rules: ", 'backup-backup') . $name);
        @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . $name);
      }

      // Auto External Removal
      $sortedMD5s = [];
      $externalStorages = ['gdrive', 'dropbox', 'onedrive', 'FTP', 'sftp', 'aws', 'wasabi', 'backupbliss'];
      foreach ($externalStorages as $storage) {
        if (isset($availableBackups['external'][$storage])) {
          $storageList = $availableBackups['external'][$storage];
          foreach ($storageList as $md5 => $data) {
            if ($storageList[$md5][6] == true && $storageList[$md5][5] == 'unlocked') {
              $sortedMD5s[] = [$storageList[$md5][1], $md5];
            }
          }
        }
      }
      $sortedMD5s = array_intersect_key($sortedMD5s, array_unique(array_map('serialize', $sortedMD5s)));
      
      usort($sortedMD5s, function ($a, $b) {
        return (strtotime($a[0]) < strtotime($b[0])) ? -1 : 1;
      });

      $sortedMD5s = array_slice($sortedMD5s, 0, -(intval(Dashboard\bmi_get_config('CRON:KEEP'))));
      foreach ($sortedMD5s as $index => $data) {
        $md5 = $data[1];

        do_action('bmi_premium_remove_backup_file', $md5);
        do_action('bmi_premium_remove_backup_json_file', $md5 . '.json');
      }
    }

    public function set_last_cron($status, $time) {
      $file = BMI_TMP . DIRECTORY_SEPARATOR . '.last';
      file_put_contents($file, $status . '.' . $time);
    }

    public function readFileSensitive($file) {
      if (!file_exists($file)) {
        echo '';
        return;
      }

      $file = new \SplFileObject($file);
      $file->seek($file->getSize());
      $total_lines = $file->key() + 1;

      $current_directory = Dashboard\bmi_get_config('STORAGE::LOCAL::PATH');
      $backups_path = $this->fixSlashes($current_directory . DIRECTORY_SEPARATOR . 'backups');
      $scanned_directory_all = array_diff(scandir($backups_path), ['..', '.']);
      $scanned_directory = array_values(preg_grep('/((.*).zip)/i', $scanned_directory_all));

      for ($i = 0; $i < $total_lines; ++$i) {

        $file->seek($i);
        $line = $this->escapeSensitive($file->current(), $current_directory, $scanned_directory);

        echo $line;
        unset($line);

      }

    }

    public function escapeSensitive($line, $current_directory, $scanned_directory) {

      global $table_prefix;
      
      $dir_name = basename($current_directory);

      $line = preg_replace('/\:\ ((.*)\.zip)/', ': *****.zip', $line);
      $line = preg_replace('/(\"filename\":(.*)\.zip)\"/', '"filename": "*****.zip"', $line);
      $line = preg_replace('/\"http(.*)\"/', '"***site_url***"', $line);
      $line = preg_replace('/\:\ http(.*)\n/', ": ***site_url***\n", $line);
      $line = preg_replace('/\"\d{10}\"/', '"***secret_login***"', $line);
      $line = str_replace(ABSPATH, '***ABSPATH***/', $line);
      $line = str_replace($dir_name, '***backup_path***', $line);
      $line = str_replace($table_prefix, '***_', $line);
      $line = preg_replace('/^(.*?&sk=).*$/', '$1***', $line);

      for ($i = 0; $i < sizeof($scanned_directory); ++$i) {

        $backup_name = $scanned_directory[$i];
        $line = str_replace($backup_name, '***some_backup***', $line);

      }

      return $line;

    }

    public function handle_cron_backup() {

      $plan_file = BMI_TMP . DIRECTORY_SEPARATOR . '.plan';
      $last_file = BMI_TMP . DIRECTORY_SEPARATOR . '.last';

      // Abort if disabled
      if (Dashboard\bmi_get_config('CRON:ENABLED') !== true) {


        if (file_exists($plan_file)) @unlink($plan_file);
        if (file_exists($last_file)) @unlink($last_file);

        return;

      }

      if (!file_exists($plan_file)) return;

      // Planned time
      $plan = intval(@file_get_contents(BMI_TMP . DIRECTORY_SEPARATOR . '.plan'));

      // Check difference
      if ((time() - $plan) > 3600) {
        Logger::log('Backup failed to run on proper time, but running now.');
        Logger::log('Planned time: ' . date('Y-m-d H:i:s', $plan));
        $this->backup_inproper_time($plan);
      }

      // Now
      $now = time();
      $this->set_last_cron('0', $now);

      // Extend execution time
      if ($this->isFunctionEnabled('headers_sent') && $this->isFunctionEnabled('session_status')) {
        if (!headers_sent() && session_status() === PHP_SESSION_DISABLED) {
          if ($this->isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
          if ($this->isFunctionEnabled('set_time_limit')) @set_time_limit(16000);
          if ($this->isFunctionEnabled('ini_set')) {
            @ini_set('max_execution_time', '259200');
            @ini_set('max_input_time', '259200');
          }
        }
      }

      if (strlen(session_id()) > 0) session_write_close();

      Logger::log(__("Automatic backup called at time: ", 'backup-backup') . date('Y-m-d, H:i:s'));

      try {
        require_once BMI_INCLUDES . '/ajax.php';
        $isBackup = (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65) ? true : false;
        $isCron = (file_exists(BMI_BACKUPS . '/.cron') && (time() - filemtime(BMI_BACKUPS . '/.cron')) <= 65) ? true : false;
        if ($isCron) {
          return;
        }

        if ($isBackup) {
          $this->handle_cron_error(__("Could not make the backup: Backup already running, please wait till it complete.", 'backup-backup'));
          $this->set_last_cron('2', $now);
        } else {
          touch(BMI_BACKUPS . '/.cron');

          if (!defined('BMI_DOING_SCHEDULED_BACKUP')) {
            define('BMI_DOING_SCHEDULED_BACKUP', true);
          }

          $handler = new BMI_Ajax();
          $handler->resetLatestLogs();
          $backup = $handler->prepareAndMakeBackup(true);
          
          if ($backup['status'] == 'success') {
            if (isset($backup['filename'])) {
              Logger::log(__("Automatic backup successed: ", 'backup-backup') . $backup['filename']);
            } else {
              Logger::log(__("Automatic backup successed", 'backup-backup'));
            }
            $this->set_last_cron('1', $now);
          } elseif ($backup['status'] == 'background') {
            Logger::log(__('Scheduled backup is running in background: ', 'backup-backup') . $backup['filename']);
            $this->set_last_cron('1', $now);
          } elseif ($backup['status'] == 'msg') {
            $this->handle_cron_error($backup['why']);
            $this->set_last_cron('3', $now);
          } else {
            $this->handle_cron_error(__("Could not make the backup due to internal server error.", 'backup-backup'));
            $this->set_last_cron('4', $now);
          }
        }
      } catch (\Exception $e) {
        $this->handle_cron_error($e);
        $this->set_last_cron('5', $now);
      } catch (\Throwable $e) {
        $this->handle_cron_error($e);
        $this->set_last_cron('5', $now);
      }
      
      $this->handle_after_cron();

      if (file_exists(BMI_BACKUPS . '/.cron')) {
        @unlink(BMI_BACKUPS . '/.cron');
      }
      require_once BMI_INCLUDES . '/cron/handler.php';
      $time = $this->get_next_cron();

      wp_clear_scheduled_hook('bmi_do_backup_right_now');
      wp_schedule_single_event($time, 'bmi_do_backup_right_now');

      $file = BMI_TMP . DIRECTORY_SEPARATOR . '.plan';
      file_put_contents($file, $time);
    }

    public function enqueue_scripts() {

      // Global
      if (in_array(get_current_screen()->id, ['toplevel_page_backup-migration', 'plugins'])) { ?>
      <script type="text/javascript">
        let stars = '<?php echo plugin_dir_url(BMI_ROOT_FILE); ?>' + 'admin/images/stars.gif';
        let css_star = "background:url('" + stars + "')";
        document.addEventListener("DOMContentLoaded", function(event) {
          jQuery('[data-slug="backup-migration-pro"]').find('strong').html('<span>Backup Migration <b style="color: orange; ' + css_star + '">Pro</b></span>');
          jQuery('[data-slug="backup-backup-pro"]').find('strong').html('<span>Backup Migration <b style="color: orange; ' + css_star + '">Pro</b></span>');
        });
      </script>
      <?php }

      // Only for BM Settings
      if (!in_array(get_current_screen()->id, ['toplevel_page_backup-migration', 'update-core', 'plugins', 'plugin-install', 'themes','customize', 'plugins-network', 'plugin-install-network', 'themes-network']) && $this->backupbliss_space_issues() === false) return;
      wp_enqueue_script('backup-migration-script', $this->get_asset('js', 'backup-migration.min.js'), ['jquery'], BMI_VERSION, true);
      wp_localize_script('backup-migration-script', 'bmiVariables', [
        'nonce' => wp_create_nonce('backup-migration-ajax'),
        'stgLoading' => __('Loading, please wait...', 'backup-backup'),
        'stgStagingDefaultName' => __('staging', 'backup-backup'),
        'urlCopies' => __('URL copied successfully', 'backup-backup'),
        'isBeforeUpdateEnabled' => dashboard\bmi_get_config('OTHER:TRIGGER:BEFORE:UPDATES') ? 'true' : 'false',
        'maxUploadSize' => $this->getMaxUploadSize()
      ]);

    }
    
    public function phpSizeToB($phpSize) {
        
      $sSuffix = strtoupper(substr($phpSize, -1));
      
      if (!in_array($sSuffix, array('P','T','G','M','K'))) {
        return (int) $phpSize;
      }
      
      $iValue = substr($phpSize, 0, -1);
      switch ($sSuffix) {
        case 'P': $iValue *= 1024;
        case 'T': $iValue *= 1024;
        case 'G': $iValue *= 1024;
        case 'M': $iValue *= 1024;
        case 'K': $iValue *= 1024;
          break;
      }
      
      return (int) $iValue;
      
    }
    
    public function getMaxUploadSize() {
      $ten = (10 * 1024 * 1024);
      $max = min($this->phpSizeToB(ini_get('post_max_size')), $this->phpSizeToB(ini_get('upload_max_filesize')), $ten);
      return intval($max / 1024 / 1024);
    }

    public function enqueue_styles() {

      // Global styles
      wp_enqueue_style('backup-migration-style-icon', $this->get_asset('css', 'bmi-plugin-icon.min.css'), [], BMI_VERSION);

      // Only for BM Settings and Update Core page and if there's no backupbliss space issues do not include the stylesheet.
      if (!in_array(get_current_screen()->id, ['toplevel_page_backup-migration', 'update-core', 'plugins', 'plugin-install', 'themes','customize', 'plugins-network', 'plugin-install-network', 'themes-network']) && $this->backupbliss_space_issues() === false) return;

      // Enqueue the style
      wp_enqueue_style('backup-migration-style', $this->get_asset('css', 'bmi-plugin.min.css'), [], BMI_VERSION);

    }

    public function handle_after_actions() {

      // Handle After Migration actions
      $afterMigrationLock = BMI_TMP . DIRECTORY_SEPARATOR . '.migrationFinished';
      if (file_exists($afterMigrationLock)) {
        if (strpos(site_url(), 'tastewp') !== false) {

          if (function_exists('wp_load_alloptions')) {
            wp_load_alloptions(true);
          }

          update_option('__tastewp_redirection_performed', true);
          update_option('auto_smart_tastewp_redirect_performed', 1);
          update_option('tastewp_auto_activated', true);
          update_option('__tastewp_sub_requested', true);

        }

        unlink($afterMigrationLock);
      }

    }

    public function handle_downloading() {
      global $wpdb;
      @error_reporting(0);
      $autologin_file = BMI_BACKUPS . '/.autologin';
      $ip = '127.0.0.1';
      if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } else {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if ($ip === false) {
          if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
        }
      }
      $allowed = ['BMI_BACKUP', 'BMI_BACKUP_LOGS', 'PROGRESS_LOGS', 'AFTER_RESTORE', 'CURL_BACKUP'];
      $get_bmi = !empty($_GET['backup-migration']) ? sanitize_text_field($_GET['backup-migration']) : false;
      $get_bid = !empty($_GET['backup-id']) ? sanitize_text_field($_GET['backup-id']) : false;
      $get_pid = !empty($_GET['progress-id']) ? sanitize_text_field($_GET['progress-id']) : false;
      $get_is_uncensored = !empty($_GET['uncensored']) ? sanitize_text_field($_GET['uncensored']) : false;
      $crons_enabled = !empty($_GET['crons']) ? sanitize_text_field($_GET['crons']) : false;
      $secret_key = !empty($_GET['sk']) ? sanitize_text_field($_GET['sk']) : false;

      if (isset($get_bmi) && in_array($get_bmi, $allowed)) {
        if (isset($get_bid) && strlen($get_bid) > 0 && isset($secret_key) && $secret_key === Dashboard\bmi_get_config('REQUEST:SECRET')) {
          $type = $get_bmi;

          if ($type == 'AFTER_RESTORE' && isset($get_pid)) {
            if (file_exists($autologin_file)) {
              $autoLoginMD = file_get_contents($autologin_file);
              $autoLoginMD = explode('_', $autoLoginMD);
              $aID = intval($autoLoginMD[0]);
              $aID2 = intval($autoLoginMD[0]) - 1;
              $aID3 = intval($autoLoginMD[0]) + 1;
              $aID4 = intval($autoLoginMD[0]) + 2;
              $aID5 = intval($autoLoginMD[0]) + 3;
              $aID6 = intval($autoLoginMD[0]) + 4;
              $aIP = $autoLoginMD[1];
              $aIZ = $autoLoginMD[2];

              // Allow 1 second delay
              $timeIsProper = false;
              if ($aID === intval($get_bid)) $timeIsProper = true;
              if ($aID2 === intval($get_bid)) $timeIsProper = true;
              if ($aID3 === intval($get_bid)) $timeIsProper = true;
              if ($aID4 === intval($get_bid)) $timeIsProper = true;
              if ($aID5 === intval($get_bid)) $timeIsProper = true;
              if ($aID6 === intval($get_bid)) $timeIsProper = true;

              if ($timeIsProper && $aIP === $ip && trim($aIZ) === $get_pid) {
                $query = new \WP_User_Query(['role' => 'Administrator', 'count_total' => false, 'fields' => 'all']);
                $sqlres = $wpdb->get_results($query->request);

                if (sizeof($sqlres) > 0 && isset($sqlres[0]->ID) && isset($sqlres[0]->user_login)) {

                  $user = $sqlres[0];
                  $adminID = $sqlres[0]->ID;
                  $adminLogin = $sqlres[0]->user_login;

                  remove_all_actions('wp_login', -1000);
                  wp_load_alloptions(true);
                  clean_user_cache(get_current_user_id());
                  clean_user_cache($adminID);
                  wp_clear_auth_cookie();
                  wp_set_current_user($adminID, $adminLogin);
                  wp_set_auth_cookie($adminID, 1, is_ssl());
                  do_action('wp_login', $adminLogin, $user);
                  update_user_caches($user);

                }
                $cronsEnabledParam = $crons_enabled ? "&crons=true" : ""; 

                $url = admin_url('admin.php?page=backup-migration' . $cronsEnabledParam);
                header('Location: ' . $url);

                @unlink($autologin_file);
                exit;
              }
            }

          } else if ($type == 'BMI_BACKUP') {
            if (Dashboard\bmi_get_config('STORAGE::DIRECT::URL') === 'true' || current_user_can('administrator')) {

              $backupname = $get_bid;
              $file = $this->fixSlashes(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupname);
              
              $outsideDir = false;
              if (!(file_exists($file) && $this->fixSlashes(dirname($file)) == $this->fixSlashes(BMI_BACKUPS))) {
                $outsideDir = true;
              }
              
              if ($outsideDir || strpos(strtolower(mime_content_type($file)), 'zip') === false) {
                header('HTTP/1.0 423 Locked');
                _e("Incorrect usage of the query request.", 'backup-backup');
                exit;
              }

              if (Dashboard\bmi_get_config('OTHER:DOWNLOAD:DIRECT') == 'true') {
                if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.htaccess')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.htaccess');
                if (file_exists(dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . '.htaccess')) @unlink(dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . '.htaccess');
                $wpcontent = trailingslashit(WP_CONTENT_DIR);
                $wpcs = strlen($wpcontent);
                $url = $this->fixSlashes(content_url(substr($file, $wpcs)), '/');
                $path = wp_redirect($url);
                exit;
              }

              // Prevent parent directory downloading
              if (ob_get_contents()) ob_end_clean();

              if ($this->isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
              if ($this->isFunctionEnabled('set_time_limit')) @set_time_limit(16000);
              if ($this->isFunctionEnabled('headers_sent') && $this->isFunctionEnabled('session_status')) {
                if (!headers_sent() && session_status() === PHP_SESSION_DISABLED) {
                  if ($this->isFunctionEnabled('ini_set')) {
                    @ini_set('max_execution_time', '259200');
                    @ini_set('max_input_time', '259200');
                    @ini_set('memory_limit', '-1');
                    if (@ini_get('zlib.output_compression')) {
                      @ini_set('zlib.output_compression', 'Off');
                    }
                  }
                }
              }

              if (strlen(session_id()) > 0) session_write_close();

              $fp = @fopen($file, 'rb');

              // header('X-Sendfile: ' . $file);
              // header('X-Sendfile-Type: X-Accel-Redirect');
              // header('X-Accel-Redirect: ' . $file);
              // header('X-Accel-Buffering: yes');
              header('Expires: 0');
              header('Pragma: public');
              header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
              header('Content-Disposition: attachment; filename="' . $backupname . '"');
              header('Content-Type: application/octet-stream');
              header('Content-Transfer-Encoding: binary');
              header('Content-Length: ' . filesize($file));
              header('Content-Description: File Transfer');
              http_response_code(200);

              if (ob_get_level()) ob_end_clean();

              fpassthru($fp);
              fclose($fp);
              exit;

            } else {
              if (ob_get_contents()) ob_end_clean();
              header('HTTP/1.0 423 Locked');
              if (ob_get_level()) ob_end_clean();
              echo __("Backup download is restricted (allowed for admins only).", 'backup-backup');
              exit;
            }
          } else if ($type == 'BMI_BACKUP_LOGS') {

            // Only Admin can download backup logs
            if (!(current_user_can('administrator') || current_user_can('do_backups'))) return;

            if (ob_get_contents()) ob_end_clean();
            $backupname = $get_bid;
            $file = $this->fixSlashes(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupname);

            // Prevent parent directory downloading
            if (file_exists($file) && $this->fixSlashes(dirname($file)) == $this->fixSlashes(BMI_BACKUPS)) {
              require_once BMI_INCLUDES . '/zipper/zipping.php';

              $zipper = new Zipper();
              $logs = $zipper->getZipFileContentPlain($file, 'bmi_logs_this_backup.log');
              header('Content-Type: text/plain');

              if ($logs) {
                header('Content-Disposition: attachment; filename="' . substr($backupname, 0, -4) . '.log"');
                http_response_code(200);
                if (ob_get_level()) ob_end_clean();

                $logs = explode('\n', $logs);
                $current_directory = Dashboard\bmi_get_config('STORAGE::LOCAL::PATH');
                $backups_path = $this->fixSlashes($current_directory . DIRECTORY_SEPARATOR . 'backups');
                $scanned_directory_all = array_diff(scandir($backups_path), ['..', '.']);
                $scanned_directory = array_values(preg_grep('/((.*).zip)/i', $scanned_directory_all));

                for ($i = 0; $i < sizeof($logs); ++$i) {

                  $line = $logs[$i];
                  echo $this->escapeSensitive($line, $current_directory, $scanned_directory) . "\n";

                }

                exit;
              } else {
                if (ob_get_level()) ob_end_clean();
                header('HTTP/1.0 404 Not found');
                echo __("There was an error during getting logs, this file is not right log file.", 'backup-backup');
                exit;
              }
            }

          } else if ($type == 'PROGRESS_LOGS') {
            $allowed_progress = [
              'latest_full.log',
              'latest.log',
              'latest_progress.log',
              'latest_migration_full.log',
              'latest_migration.log',
              'latest_migration_progress.log',
              'latest_staging_full.log',
              'latest_staging.log',
              'latest_staging_progress.log',
              'complete_logs.log'
            ];
            if (isset($get_pid) && in_array($get_pid, $allowed_progress)) {

              $restricted_progress = ['complete_logs.log'];
              if (in_array($get_pid, $restricted_progress)) {

                // Only Admin can download backup logs
                if (!(current_user_can('administrator') || current_user_can('do_backups'))) return;

              }

              header('Content-Type: text/plain');
              header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
              http_response_code(200);
              if (ob_get_contents()) ob_end_clean();
              if ($get_pid == 'complete_logs.log') {
                $file = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log';
                if (ob_get_level()) ob_end_clean();
                $this->readFileSensitive($file);
                exit;
              } else if ($get_pid == 'latest_full.log') {
                $progress = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_progress.log';
                $logs = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest.log';
                if ((file_exists($progress) && file_exists($logs) && ((time() - filemtime($progress)) < (60 * 1))) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($progress);
                  echo "\n";
                  if (isset($get_is_uncensored) && $get_is_uncensored && current_user_can('administrator')) readfile($logs);
                  else $this->readFileSensitive($logs);
                  exit;
                } else {
                  if (file_exists($progress) && !(time() - filemtime($progress)) < (60 * 1)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-backup') . "\n";
                    echo __("Human readable: file expired.", 'backup-backup');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              } else if ($get_pid == 'latest_migration_full.log') {
                $progress = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration_progress.log';
                $logs = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration.log';
                if ((file_exists($progress) && file_exists($logs) && ((time() - filemtime($progress)) < (60 * 1))) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($progress);
                  echo "\n";
                  if (isset($get_is_uncensored) && $get_is_uncensored && current_user_can('administrator')) readfile($logs);
                  else $this->readFileSensitive($logs);
                  exit;
                } else {
                  if (file_exists($progress) && !(time() - filemtime($progress)) < (60 * 1)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-backup') . "\n";
                    echo __("Human readable: file expired.", 'backup-backup');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              } else if ($get_pid == 'latest_staging_full.log') {
                $progress = BMI_STAGING . DIRECTORY_SEPARATOR . 'latest_staging_progress.log';
                $logs = BMI_STAGING . DIRECTORY_SEPARATOR . 'latest_staging.log';
                if ((file_exists($progress) && file_exists($logs) && ((time() - filemtime($progress)) < (60 * 1))) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($progress);
                  echo "\n";
                  $this->readFileSensitive($logs);
                  exit;
                } else {
                  if (file_exists($progress) && !(time() - filemtime($progress)) < (60 * 1)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-backup') . "\n";
                    echo __("Human readable: file expired.", 'backup-backup');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              } else {
                $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $get_pid;
                if ($get_pid == 'latest_staging.log') $file = BMI_STAGING . DIRECTORY_SEPARATOR . $get_pid;
                if ($get_pid == 'latest_staging_progress.log') $file = BMI_STAGING . DIRECTORY_SEPARATOR . $get_pid;
                if (file_exists($file) && (((time() - filemtime($file)) < (60 * 1)) || current_user_can('administrator'))) {
                  if (ob_get_level()) ob_end_clean();

                  if (isset($get_is_uncensored) && $get_is_uncensored && current_user_can('administrator')) readfile($file);
                  else $this->readFileSensitive($file);

                  echo "\n";
                  if ($get_pid == 'latest.log') $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_progress.log';
                  if ($get_pid == 'latest_migration.log') $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration_progress.log';
                  if ($get_pid == 'latest_staging.log') $file = BMI_STAGING . DIRECTORY_SEPARATOR . 'latest_staging_progress.log';
                  echo __("[DOWNLOAD GENERATED] File downloaded on (server time): ", 'backup-backup') . date('Y-m-d H:i:s') . "\n";
                  echo __("[DOWNLOAD GENERATED] Last update (seconds): ", 'backup-backup') . (time() - filemtime($file)) . __(" seconds ago ", 'backup-backup') . "\n";
                  echo __("[DOWNLOAD GENERATED] Last update (date): ", 'backup-backup') . date('Y-m-d H:i:s', filemtime($file)) . " \n";
                  exit;
                } else {
                  if (file_exists($file) && !(time() - filemtime($file)) < (60 * 1)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-backup') . "\n";
                    echo __("Human readable: file expired.", 'backup-backup');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              }
            }
          } else if ($type == 'CURL_BACKUP') {
            
            // We tried to use nonces here, but turns out that WordPress does not work well with generating nonces for cURL session.
            // We also tries to use cookiejar etc. but for researchers, this function is "verified" by process identy.
            // It's similarly safe as nonce, but it works in case we need, nonces gets rejected after second request.
            // 
            // At the end, user who indeed would like to abuse this functionality, he can't do anything than helping the site owner.
            // Free browser will keep the process ongoing, user won't receive any details other than "success" - as long as the user know the process identy.
            
            try {

              // Load bypasser
              require_once BMI_INCLUDES . '/backup-process.php';
              $request = new Bypasser($get_bid, BMI_CONFIG_DIR, trailingslashit(WP_CONTENT_DIR), BMI_BACKUPS, trailingslashit(ABSPATH), plugin_dir_path(BMI_ROOT_FILE));
              
              if (sizeof($request->remote_settings) === 0) return;

              // Handle request
              $request->handle_batch();
              exit;

            } catch (\Exception $e) {

              error_log('There was an error with Backup Migration plugin: ' . $e->getMessage());
              Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#01' . '|' . $e->getMessage());
              error_log(strval($e));

            } catch (\Throwable $t) {
              
              error_log('There was an error with Backup Migration plugin: ' . $t->getMessage());
              Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#01' . '|' . $t->getMessage());
              error_log(strval($t));

            }
            
          }
        }
      }
    }

    public function deactivation() {
      Logger::log(__("Plugin has been deactivated", 'backup-backup'));
      $this->revertLitespeed();
    }

    public static function res($array) {
      $GLOBALS['BMI::RESPONSE::SENT'] = true;
      echo json_encode(Backup_Migration_Plugin::sanitize($array));

      if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {
        Logger::log('CLI response:');
        Logger::log(json_encode(Backup_Migration_Plugin::sanitize($array)));
      }

      exit;
    }

    public static function getAvailableMemoryInBytes() {

      $totalMemory = @ini_get('memory_limit');
      if ($totalMemory == -1) {

        $totalMemory = 32 * 1024 * 1024;

      } else {

        if (strpos($totalMemory, 'M') !== false || strpos($totalMemory, 'm') !== false) {
          $totalMemory = intval($totalMemory) * 1024 * 1024;
        } else if (strpos($totalMemory, 'G') !== false || strpos($totalMemory, 'g') !== false) {
          $totalMemory = intval($totalMemory) * 1024 * 1024 * 1024;
        } else if (strpos($totalMemory, 'K') !== false || strpos($totalMemory, 'k') !== false) {
          $totalMemory = intval($totalMemory) * 1024;
        } else {
          $totalMemory = intval($totalMemory);
        }

      }

      $availableMemory = $totalMemory - memory_get_usage(true);

      return $availableMemory;

    }

    public static function sanitize($data = []) {
      $array = [];

      if (is_array($data) || is_object($data)) {
        foreach ($data as $key => $value) {
          $key = ((is_numeric($key))?intval($key):sanitize_text_field($key));

          if (is_array($value) || is_object($value)) {
            $array[$key] = Backup_Migration_Plugin::sanitize($value);
          } else {
            $array[$key] = sanitize_text_field($value);
          }
        }
      } elseif (is_string($data)) {
        return sanitize_text_field($data);
      } elseif (is_bool($data)) {
        return $data;
      } elseif (is_null($data)) {
        return 'false';
      } else {
        Logger::log(__("Unknow AJAX Sanitize Type: ", 'backup-backup') . gettype($data));
        wp_die();
      }

      return $array;
    }

    public static function fixLitespeed() {
      $litepath = BMI_INCLUDES . DIRECTORY_SEPARATOR . 'htaccess' . DIRECTORY_SEPARATOR . '.litespeed';
      $htpath = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
      if (!is_writable($htpath)) return ['status' => 'success'];
      if (file_exists($htpath)) {
        Backup_Migration_Plugin::revertLitespeed();
        $litespeed = @file_get_contents($litepath);
        $htaccess = @file_get_contents($htpath);
        $htaccess = explode("\n", $htaccess);
        $litespeed = explode("\n", $litespeed);

        $hasAlready = false;
        for ($i = 0; $i < sizeof($htaccess); ++$i) {
          if (strpos($htaccess[$i], 'Backup Migration') !== false) {
            $hasAlready = true;

            break;
          }
        }

        if ($hasAlready) {
          return ['status' => 'success'];
        }
        $htaccess[] = '';
        for ($i = 0; $i < sizeof($litespeed); ++$i) {
          $htaccess[] = $litespeed[$i];
        }

        file_put_contents($htpath, implode("\n", $htaccess));
      } else {
        copy($litepath, $htpath);
      }

      return ['status' => 'success'];
    }

    public static function revertLitespeed() {
      $htpath = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
      $addline = true;

      if (!is_writable($htpath)) return ['status' => 'success'];
      $htaccess = @file_get_contents($htpath);
      $htaccess = explode("\n", $htaccess);
      $htFilter = [];

      for ($i = 0; $i < sizeof($htaccess); ++$i) {
        if (strpos($htaccess[$i], 'Backup Migration START')) {
          $addline = false;

          continue;
        } elseif (strpos($htaccess[$i], 'Backup Migration END')) {
          $addline = true;

          continue;
        } else {
          if ($addline == true) {
            $htFilter[] = $htaccess[$i];
          }
        }
      }

      file_put_contents($htpath, trim(implode("\n", $htFilter)));

      return ['status' => 'success'];
    }

    public static function humanSize($bytes) {
      if (is_int($bytes)) {
        $label = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

        return (round($bytes, 2) . " " . $label[$i]);
      } else return $bytes;
    }

    public static function fixSlashes($str, $slash = false) {
      // Old version
      // $str = str_replace('\\\\', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('\\', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('\/', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('/', DIRECTORY_SEPARATOR, $str);

      // if ($str[strlen($str) - 1] == DIRECTORY_SEPARATOR) {
      //   $str = substr($str, 0, -1);
      // }
      
      // Since 1.3.2
      $protocol = '';
      if ($slash == false) $slash = DIRECTORY_SEPARATOR;
      if (substr($str, 0, 7) == 'http://') $protocol = 'http://';
      else if (substr($str, 0, 8) == 'https://') $protocol = 'https://';
      
      $str = substr($str, strlen($protocol));
      $str = preg_replace('/[\\\\\/]+/', $slash, $str);
      $str = untrailingslashit($str);

      return $protocol . $str;
    }

    public static function canShareLogsOrShouldAsk() {

      return 'not-allowed';

      // REMOVED CODE:
      // $isAllowed = get_option('BMI_LOGS_SHARING_IS_ALLOWED', 'unknown');
      // $isAllowedConfig = Dashboard\bmi_get_config('LOGS::SHARING');
      //
      // if ($isAllowed == 'unknown' || empty($isAllowedConfig)) return 'ask';
      // else if ($isAllowed === 'yes' && $isAllowedConfig === 'yes') {
      //   return 'allowed';
      // } else if ($isAllowed === 'no' && $isAllowedConfig === 'no') {
      //   return 'not-allowed';
      // } else return 'ask';

    }

    public static function getRecentSize() {
      $folderNames = [ 'BACKUP:DATABASE' => 'database',
        "BACKUP:FILES::PLUGINS" => 'plugins',
        "BACKUP:FILES::UPLOADS" => 'uploads',
        "BACKUP:FILES::THEMES" => 'themes',
        "BACKUP:FILES::OTHERS" => 'contents_others',
        "BACKUP:FILES::WP" => 'wordpress'
      ];

      $size = 0;
      foreach ($folderNames as $setting => $fileName) {
        if (Dashboard\bmi_get_config($setting) === 'true') {
          $size += get_transient('bmi_latest_size_' . $fileName);
        }

      }
      return $size;
    }

    public static function merge_arrays(&$array1, &$array2) {
      for ($i = 0; $i < sizeof($array2); ++$i) {
        $array1[] = $array2[$i];
      }
    }

    public static function getDefaultDisabledPaths() {
        require_once BMI_INCLUDES . '/staging/controller.php';
        $staging = new Staging('..ajax..');
        $stagingSites = $staging->getStagingSites(true);
        $stagingSitesPaths = [];
        // Get all directory names of staging sites
        foreach ($stagingSites as $index => $site) {

          // Convert every directory to their location path
          $stagingSitesPaths[] = '***ABSPATH***/' . $site['name'];

        }
    
        $ignored_paths_default = [
          BMI_CONFIG_DIR,
          BMI_BACKUPS,
          BMI_ROOT_DIR,
          constant('BMI_PRO_ROOT_DIR'),
          "***ABSPATH***/wp-content/ai1wm-backups",
          "***ABSPATH***/wp-content/ai1wm-backups-old",
          "***ABSPATH***/wp-content/mwp-download",
          "***ABSPATH***/wp-content/uploads/wp-clone",
          "***ABSPATH***/wp-content/updraft",
          "***ABSPATH***/wp-content/backups-dup-pro",
          "***ABSPATH***/wp-content/wpvividbackups",
          "***ABSPATH***/wp-content/backup-guard",
          "***ABSPATH***/wp-content/backuply",
          "***ABSPATH***/wp-content/backups-dup-lite",
          "***ABSPATH***/wp-content/uploads/backupbuddy_backups",
          "***ABSPATH***/wp-content/uploads/wp-file-manager-pro",
          "***ABSPATH***/wp-content/uploads/wp-file-manager",
          "***ABSPATH***/wp-content/plugins/akeebabackupwp",
          "***ABSPATH***/wp-content/uploads/jetbackup",
          "***ABSPATH***/wp-content/uploads/backup-guard",
          "***ABSPATH***/wp-content/uploads/wp-migrate-db",
          "***ABSPATH***/wp-content/uploads/wpforms/.htaccess.cpmh3129",
          "***ABSPATH***/wp-content/uploads/gravity_forms/.htaccess.cpmh3129",
          "***ABSPATH***/.htaccess.cpmh3129",
          "***ABSPATH***/logs/traffic.html/.md5sums",
          "***ABSPATH***/wp-config.php",
          "***ABSPATH***/wp-content/backup-migration-config.php",
        ];
        $ignored_paths = array_merge($ignored_paths_default, $stagingSitesPaths);
        array_walk($ignored_paths, function(&$path){
          $path = self::fixSlashes(str_replace('***ABSPATH***', ABSPATH, $path));
        });
        return $ignored_paths;
    }

    private function get_asset($base = '', $asset = '') {
      return BMI_ASSETS . '/' . $base . '/' . $asset;
    }

    /**
     * Extend the execution time for the plugin
     * 
     * @return void
     */
    public static function extend_execution_time() {
      if (self::isFunctionEnabled('headers_sent') && self::isFunctionEnabled('session_status')) {
        if (!headers_sent() && session_status() === PHP_SESSION_DISABLED) {
          if (self::isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
          if (self::isFunctionEnabled('set_time_limit')) @set_time_limit(16000);
          if (self::isFunctionEnabled('ini_set')) {
            @ini_set('max_execution_time', '259200');
            @ini_set('max_input_time', '259200');
          }
        }
      }
    }

    public static function getRetryAfterIfAvailable($ch, $response)
    {
      $phpVersion = phpversion();
      $curlVersion = curl_version();
      // Available as of PHP 8.2.0 and cURL 7.66.0
      if (version_compare($phpVersion, '8.2.0', '>=') && version_compare($curlVersion['version'], '7.66.0', '>=')) {
        $retryAfter = curl_getinfo($ch, CURLINFO_RETRY_AFTER);
        if ($retryAfter !== false) {
          return $retryAfter;
        }
      }else {
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $headers = explode("\r\n", $header);
        foreach ($headers as $h) {
            if (preg_match('/^Retry-After:\s+(\d+)/i', $h, $matches)) {
                return intval($matches[1]);
            }
        }    
      }
      return false;

    }
  }
