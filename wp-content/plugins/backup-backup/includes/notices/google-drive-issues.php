<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\External\BMI_External_GDrive as GDrive;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  require_once BMI_INCLUDES . '/external/google-drive.php';
  $GDrive = new GDrive();

  $gdriveIssue = get_transient('bmip_gd_issue');
  
  if (!$gdriveIssue) return;

  switch ($gdriveIssue) { // REST OF GoogleDrive Notices in backup-backup/include/dashboard/modules/quota-errors.php
    case 'auth_error_disconnected':
      $message = sprintf(
        __('There was an error authenticating your Google Drive account. Please click %shere%s to re-authenticate, or click %shere%s to disable Google Drive as an external storage option.', 'backup-backup'),
        '<a href="javascript:document.getElementById(\'bmi-error-dismiss\').click();document.getElementById(\'gdrive-connect-btn\').click();">',
        '</a>',
        '<a href="javascript:document.getElementById(\'bmi-error-dismiss\').click();document.getElementById(\'bmi-pro-storage-gdrive-toggle\').checked=false;document.querySelector(\'#storage-options .save-btn\').click(); setTimeout(()=>{window.location.reload()}, 500);">',
        '</a>'
      );
      break;

    default:
    return;
  }

  if (!isset($message) || get_option('bmip_gdrive_dismiss_issue', false)) return;
?>


<div class="error-noticer" id="gdrive-issues">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('We have some error regarding most recent backup upload process.', 'backup-backup'); ?>
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
    <?php echo $message; ?>
  </div>
</div>