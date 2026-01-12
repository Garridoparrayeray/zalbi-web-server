<?php

  // Namespace
  namespace BMI\Plugin;
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\BMI_Logger as Logger;
  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * Offline Methods Manager
   */
  class BMI_Offline {

    public $ajaxInserted = false;

    /**
     * __construct - Initializer (loads offline modules)
     */
    function __construct() {
      add_action('bmi_ajax_offline', function($post=[]){
        if (BMI_DEBUG)
            Logger::error("FREE AJAX OFFLINE");
        require_once BMI_INCLUDES . '/ajax_offline.php';
        $ajaxoffline = new BMI_Ajax_Offline($post);
      });
      add_action('wp_ajax_bmip_keepalive', [&$this, 'initializeOfflineAjax']);
      add_action('wp_ajax_nopriv_bmip_keepalive', [&$this, 'initializeOfflineAjax']);

      if (is_user_logged_in() && current_user_can('administrator')) {
        add_action('wp_ajax_backup_migration', [&$this, 'initializeOfflineAjax']);
      }

      add_filter('allowed_http_origins', function ($origins) {
        $origins[] = 'https://backupbliss.com';
        $origins[] = 'https://api.backupbliss.com';
        return $origins;
      });

      // $TBU = get_option('bmip_to_be_uploaded', false);
      // if ($TBU != false && (sizeof($TBU['current_upload']) > 0 || sizeof($TBU['queue']) > 0)) {
        
      // }

      add_action('wp_head', [&$this, 'keepAliveJS']);
      add_action('admin_head', [&$this, 'keepAliveJS']);
      add_action('wp_footer', [&$this, 'keepAliveJS']);
      add_action('admin_footer', [&$this, 'keepAliveJS']);

    }

    /**
     * initializeOfflineAjax - Initialized Offline handlers for Ajax
     *
     * @return void
     */
    public function initializeOfflineAjax() {

      // if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

        // Extend execution time
        if (BMP::isFunctionEnabled('headers_sent') && BMP::isFunctionEnabled('session_status')) {
          if (!headers_sent() && session_status() === PHP_SESSION_DISABLED) {
            if (BMP::isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
            if (BMP::isFunctionEnabled('set_time_limit')) @set_time_limit(16000);
            if (BMP::isFunctionEnabled('ini_set')) {
              @ini_set('max_execution_time', '259200');
              @ini_set('max_input_time', '259200');
            }
          }
        }

        if ((isset($_GET['token']) && ($_GET['token'] == 'bmip' || $_GET['token'] == 'bmi') && isset($_GET['f']))) {

          if (empty($_GET)) return;

          // Sanitize User Input
          $post = BMP::sanitize($_GET);          

        } else if ((isset($_POST['token']) && ($_POST['token'] == 'bmip' || $_POST['token'] == 'bmi') && isset($_POST['f']))) {

          if (empty($_POST)) return;

          // Sanitize User Input
          $post = BMP::sanitize($_POST);

        }

        if (!empty($post)) {
            do_action("bmi_ajax_offline", $post);
        }

        // Execution error due to time limit
        // register_shutdown_function([$this, 'execution_shutdown']);

      // }

    }

    public function keepAliveJS() {
      if ($this->ajaxInserted) return;

      ?>
      <script defer type="text/javascript" id="bmip-js-inline-remove-js">
        function objectToQueryString(obj){
          return Object.keys(obj).map(key => key + '=' + obj[key]).join('&');
        }

        function globalBMIKeepAlive() {
          let xhr = new XMLHttpRequest();
          let data = { action: "bmip_keepalive", token: "bmip", f: "refresh" };
          let url = '<?php echo admin_url("admin-ajax.php"); ?>' + '?' + objectToQueryString(data);
          xhr.open('POST', url, true);
          xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
          xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
              let response;
              if (response = JSON.parse(xhr.responseText)) {
                if (typeof response.status != 'undefined' && response.status === 'success') {
                  //setTimeout(globalBMIKeepAlive, 3000);
                } else {
                  //setTimeout(globalBMIKeepAlive, 20000);
                }
              }
            }
          };

          xhr.send(JSON.stringify(data));
        }

        document.querySelector('#bmip-js-inline-remove-js').remove();
      </script>
      <?php

      $this->ajaxInserted = true;
    }

  }
