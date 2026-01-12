<?php
/*
Plugin Name:  MyPopUps
Description:  Create pop-ups without coding! Our builder allows you to create amazing looking pop-ups for a wide range of purposes (newsletter subscription, discounts, cookies etc.).
Version:      1.2.8
Text-domain:  pop-up-pop-up
Author:       Inisev
Author URI:   https://inisev.com
Plugin URI:   https://mypopups.com
License:      GPLv3
License URI:  http://www.gnu.org/licenses/gpl-3.0.en.html
*/

define('MYPOPUPS_URL', 'https://mypopups.com');
define('MYPOPUPS_DOMAIN_CHECK_FILE', 'mypopups_domain_check.json');
define('MPU_PLUGIN_VERSION_CURRENT', '1.2.8');

if (!function_exists('analyst_init')) {
  require_once 'analyst/main.php';
}
analyst_init(array(
  'client-id' => 'o6grd4ebow48kyeq',
  'client-secret' => 'ae54530aa6229bd48abaf85c80e394d3fc373808',
  'base-dir' => __FILE__
));

// Front area - everyone
$mpu_script_already_inserted = false;
function mypopups_wp_insert_script_hook() {

  // Do not display pop-ups under /wp-admin section
  if (is_admin()) return;

  global $mpu_script_already_inserted;

  if ($mpu_script_already_inserted) return;
  else $mpu_script_already_inserted = true;

  $_mpu_already = [];
  $options = get_option('wp_mypopups');

  if (empty($options) || (isset($options['list']) && empty($options['list']))) {
    return;
  }

  $options['list'] = array_reverse($options['list']);

  foreach ($options['list'] as $id => $item) {

    $embed = $item['embed_url'];
    $url = $embed;
    $embed = substr($embed, strpos($embed, 'element?sub'));

    if ($item['status'] == 'Enabled' && !in_array($embed, $_mpu_already)) {

      // Sanitize attributes
      $idT = 'wp_mypopups-' . sanitize_text_field(esc_html($id));
      $urlT = sanitize_text_field(esc_url($url));

      // Remove any quotation
      $idT = str_replace('"', '', $idT);
      $idT = str_replace("'", '', $idT);
      $idT = str_replace(":", '', $idT);
      $urlT = str_replace('"', '', $urlT);
      $urlT = str_replace("'", '', $urlT);

      // Allow only valid URL that starts from https://
      if (substr($urlT, 0, 8) == 'https://') {

        $urlT = substr($urlT, 8);
        $urlT = str_replace(":", '', $urlT);
        $urlT = 'https://' . $urlT;

        // Enqueue script
        wp_enqueue_script(esc_attr(esc_html($idT)), esc_attr(esc_html($urlT)), [], MPU_PLUGIN_VERSION_CURRENT);

        // Push displayed pop-up scripts to "already displayed" list.
        array_push($_mpu_already, $embed);

      }

    }

  }

};

// Front area - register scripts (everyone)
add_action('wp_head', 'mypopups_wp_insert_script_hook', 1000000);
add_action('wp_footer', 'mypopups_wp_insert_script_hook', -1000000);
add_action('wp_print_styles', 'mypopups_wp_insert_script_hook', 1000000);
add_action('wp_print_scripts', 'mypopups_wp_insert_script_hook', 1000000);
add_action('wp_enqueue_scripts', 'mypopups_wp_insert_script_hook', 1000000);
add_action('wp_print_footer_scripts', 'mypopups_wp_insert_script_hook', -1000000);

// Admin area
// Register menu
add_action('admin_menu', function () {

  // Prevent unauthorized from access
  if (!current_user_can('activate_plugins') || !is_admin()) {
    return;
  }

  $icon = 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 245 300" style="enable-background:new 0 0 245 300;" xml:space="preserve"><g><path style="fill:#00B47C;" d="M148.3,257.7c-18.5-0.5-34.8-9.5-45.5-23.1c-5-5.2-8.1-12.3-8.1-20.1V104.1c0-14.9-11.3-27.4-26.1-28.9 l-32-3.2v129.1c0,47.5,37.2,87.6,84.6,88.9c49.2,1.3,89.5-38.1,89.5-87v-6C210.7,231.1,182.6,258.6,148.3,257.7z"/><path style="fill:#028C85;" d="M230.8,48.9l-45.1-37.6c-2.2-1.8-5.3-1.8-7.4,0L133.1,49c-1.7,1.4-1.3,4.1,0.7,4.9c1.9,0.8,4.4,1.7,7.5,2.4 c6.6,1.6,11.4,7.3,11.4,14.1v143.1c0,15.6-11.9,29.1-27.4,30c-8.9,0.5-16.9-3.1-22.5-8.9c10.8,13.6,27,22.6,45.5,23.1  c34.3,0.9,62.5-26.6,62.5-60.7V70.5c0-6.4,5.2-12.7,11.5-14.1c3.2-0.7,5.9-1.7,7.8-2.5C232.1,53,232.5,50.3,230.8,48.9z"/></g></svg>');
  add_menu_page(__('MyPopUps', 'pop-up-pop-up'), __('MyPopUps', 'pop-up-pop-up'), 'manage_options', 'wp-mypopups', 'wp_mypopups_settings_page', '', 100);

});

// Admin page scripts
add_action('admin_enqueue_scripts', function ($hook) {

  if ('toplevel_page_wp-mypopups' == $hook) {
    wp_enqueue_style('wp_mypopups_admin_css', plugins_url('css/admin-style.css', __FILE__), [], MPU_PLUGIN_VERSION_CURRENT);
    wp_enqueue_script('underscore', plugins_url('js/underscore-min.js', __FILE__), [], MPU_PLUGIN_VERSION_CURRENT);
    wp_enqueue_script('wp_mypopups_script', plugins_url('js/admin-script.js', __FILE__), [], MPU_PLUGIN_VERSION_CURRENT);
    wp_localize_script('wp_mypopups_script', 'mypopups_localize_script', [
      'nonce' => wp_create_nonce('mypopups_ajax_nonce')
    ]);
  }

  wp_enqueue_style('wp_mypopups_icon_css', plugins_url('css/MPU-icon-style.css', __FILE__), [], MPU_PLUGIN_VERSION_CURRENT);

});

// Determine which MPU version is used
add_action('wp_head', function () {

  echo '<meta name="mpu-version" content="' . esc_attr(MPU_PLUGIN_VERSION_CURRENT) . '" />';

});

// Footer text left
add_filter('admin_footer_text', function ($footer_text) {

  $current_screen = get_current_screen();
  if (isset($current_screen->id) && 'toplevel_page_wp-mypopups' == $current_screen->id) {
    $footer_text = sprintf(__('Need help? Go to our <a href="%s">support center</a>.', 'pop-up-pop-up'), MYPOPUPS_URL . '/help');
  }

  return $footer_text;

});

// Footer text right
add_filter('update_footer', function ($footer_text) {

  $current_screen = get_current_screen();
  if (isset($current_screen->id) && 'toplevel_page_wp-mypopups' == $current_screen->id) {
    $footer_text = __('Powered by <b>MyPopUps</b>', 'pop-up-pop-up');
  }

  return $footer_text;

}, 11);

// Redirect to plugin settings page after activation
add_action('admin_init', function () {

  if (get_option('wp_mypopups_do_activation_redirect', false)) {
    delete_option('wp_mypopups_do_activation_redirect');
    wp_redirect(admin_url('admin.php?page=wp-mypopups'));
  }

});

// Save data from ajax query from plugin settings page
add_action('wp_ajax_wp_mypopups', function () {

  // Nonce verification
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'mypopups_ajax_nonce')) {
    return;
  }

  // Prevent unauthorized from access
  if (!current_user_can('activate_plugins') || !is_admin()) {
    return;
  }

  // Allow to call this hook only by our plugin settings page
  if (!(isset($_POST['call_handler']) && $_POST['call_handler'] === 'pop-up-pop-up_main_ajax_hook')) {
    return;
  }

  if (!empty($_POST) && isset($_POST['user_id'])) {
    $message = wp_mypopups_add_user_id_to_file_code(sanitize_text_field($_POST['user_id']));
    header('Content-Type: application/json');
    $response = $message ? $message : __('saved', 'pop-up-pop-up');

    echo json_encode(['message' => sanitize_text_field($response)]);

    wp_die();
  }

  if (!empty($_POST) && !empty($_POST['list']) && (is_object($_POST['list']) || is_array($_POST['list']))) {
    $options = [ 'list' => [] ];

    foreach ($_POST['list'] as $key => $popup) {
      $slug = preg_replace("/[^a-z0-9]/", '', wp_mypopups_object_sanitize($key));
      $popup['slug'] = $slug;
      $options['list'][$slug] = wp_mypopups_object_sanitize($popup);
    }

    update_option('wp_mypopups', $options);
    header('Content-Type: application/json');
    $options['status'] = __('options saved', 'pop-up-pop-up');
    echo json_encode($options);
    wp_die();
  }

  if (!empty($_POST) && !empty($_POST['agreed'])) {
    if (sanitize_text_field($_POST['agreed']) === 'true') {
      update_option('wp_mypopups_connect', true);
      wp_mypopups_file_code();
    }
  }

});

// Get path of save
function wp_mypopups_get_file_path() {

  if (is_writable(get_home_path())) {
    $path = get_home_path();
  } else if (is_writable(ABSPATH)) {
    $path = ABSPATH;
  } else if (is_writable(__DIR__)) {
    $path = trailingslashit(__DIR__);
  } else {
    $path = get_home_path();
  }

  $file = $path . MYPOPUPS_DOMAIN_CHECK_FILE;
  return $file;

}

// Show plugin settings page
function wp_mypopups_settings_page() {

  // Prevent unauthorized from access
  if (!current_user_can('activate_plugins') || !is_admin()) {
    return;
  }

  $options = get_option('wp_mypopups');
  if (empty($options)) {
    $options = [
      'list' => []
    ];
    update_option('wp_mypopups', $options);
  }
  include plugin_dir_path(__FILE__) . '/views/main.php';

}

// Check or add file to site root
function wp_mypopups_file_code() {

  // Prevent unauthorized from access
  if (!current_user_can('activate_plugins') || !is_admin()) {
    return;
  }

  if (!get_option('wp_mypopups_connect', false)) {
    return;
  }

  $file = wp_mypopups_get_file_path();
  if (file_exists($file)) {
    return;
  }
  $domain = preg_replace("/^www./", '', site_url());
  if (substr($domain, 0, 8) === 'https://') $domain = substr($domain, 8);
  if (substr($domain, 0, 7) === 'http://') $domain = substr($domain, 7);
  $response = wp_remote_get(MYPOPUPS_URL . '/api/domains/get-code/' . $domain, [ 'sslverify' => false ]);
  $message = false;
  if ($response && isset($response['body'])) {
    $body = json_decode($response['body'], true);
    if (isset($body) && $body['success']) {
      $uuid = $body['code'];
      $fp = fopen($file, 'w');
      if ($fp) {
        fwrite($fp, json_encode(["code" => $uuid]));
        fclose($fp);
      } else {
        $message = __('Please check permission, I could not save this file: ', 'pop-up-pop-up') . $file;
      }
    } else if (isset($body['message'])) {
      $message = $body['message'];
    }
  } else {
    $message = __('Server not return domain code', 'pop-up-pop-up');
  }

  return $message;

}

// Delete code file from site on plugin deactivation or uninstall
function wp_mypopups_delete_file_code() {

  // Prevent unauthorized from access
  if (!current_user_can('activate_plugins') || !is_admin()) {
    return;
  }

  $file = wp_mypopups_get_file_path();
  delete_option('wp_mypopups');
  delete_option('wp_mypopups_connect');
  if (file_exists($file)) {
    unlink($file);
  }

}

// Add user id to code file
function wp_mypopups_add_user_id_to_file_code($id) {

  $file = wp_mypopups_get_file_path();
  if (!file_exists($file)) {
    return;
  }
  $content = file_get_contents($file);

  try {
    $json_data = json_decode($content, true);
  } catch (Exception $e) {
    $json_data = false;
  }
  $message = false;
  if ($json_data and is_array($json_data)) {
    $json_data['id'] = $id;
    $fp = fopen($file, 'w');
    if ($fp) {
      fwrite($fp, json_encode($json_data));
      fclose($fp);
    } else {
      $message = __('Please check permission, I could not save this file: ', 'pop-up-pop-up') . $file;
    }
  }

  return $message;

}

// Recursive sanitization function
function wp_mypopups_object_sanitize($data = []) {
  $array = [];

  if (is_array($data) || is_object($data)) {
    foreach ($data as $key => $value) {
      $key = ((is_numeric($key))?intval($key):sanitize_text_field($key));

      if (is_array($value) || is_object($value)) {
        $array[$key] = wp_mypopups_object_sanitize($value);
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
    error_log("Unknow AJAX Sanitize Type: " . gettype($data));
    return '';
  }

  return $array;
}

// Register internal hooks
register_uninstall_hook(__FILE__, "wp_mypopups_delete_file_code");
register_deactivation_hook(__FILE__, "wp_mypopups_delete_file_code");
register_activation_hook(__FILE__, function () {
  add_option('wp_mypopups_do_activation_redirect', true);
});

// Include footer banner
include_once trailingslashit(__DIR__) . 'modules/banner/misc.php';

// Review banner
add_action('plugins_loaded', function () {

  if (!(class_exists('Inisev\Subs\Inisev_Review') || class_exists('Inisev_Review'))) require_once __DIR__ . '/modules/review/review.php';
  $review_banner = new \Inisev\Subs\Inisev_Review(__FILE__, __DIR__, 'pop-up-pop-up', 'MyPopUps', 'https://bit.ly/3xbZfIW', 'wp-mypopups');

});
