<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;
  use BMI\Plugin\Backup_Migration_Plugin AS BMP;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  $securityPlugins = BMP::get_active_security_plugins();

  if (empty($securityPlugins)) {
    return;
  }

  if (get_option('bmi_security_warning_dismiss', false) == true) {
    return;
  }
  // add to the key value <b></b> to make it bold
  array_walk($securityPlugins, function(&$plugin) {
    $plugin = '<b style="display: inline-block">' . esc_html($plugin) . '</b>';
  });
  $pluginsList = implode(', ', $securityPlugins);


?>


<div class="error-noticer warn" id="security-plugin-warning">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('Security Restrictions May Affect Backup or Download', 'backup-backup'); ?>
      </div>
      <div class="right hoverable">
        <span class="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-backup'); ?>" data-collapse="<?php _e('Collapse', 'backup-backup'); ?>">
          <?php _e('Expand', 'backup-backup'); ?>
        </span> |
        <span id="bmi-error-dismiss">
          <?php _e('Dismiss', 'backup-backup'); ?>
        </span>
      </div>
    </div>
  </div>
  <div class="error-body">
    <?php
      $errorBody = sprintf(
        __('We noticed you\'re using %s. Security plugins can sometimes block backup creation or backup download process, depending on how strict their settings are. In such cases, you can either whitelist Backup Migration plugin, ease up on restrictions or just temporarily disable security plugins, until backups/migrations are performed.', 'backup-backup'),
        $pluginsList
      );
      _e($errorBody, 'backup-backup');
    ?>
  </div>
</div>
