<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\External\BMI_External_Dropbox as Dropbox;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  require_once BMI_INCLUDES . 'external/dropbox.php';
  $dropbox = new Dropbox();

  $dropboxIssue = get_transient('bmip_dropbox_issue');
  wp_load_alloptions(true);
  $expireTime = get_option('_transient_timeout_bmip_dropbox_issue', time() + HOUR_IN_SECONDS);
  $timeToRetry = human_time_diff($expireTime, time());
  
  if (!$dropboxIssue) return;

  switch ($dropboxIssue) {
    case 'auth_error_disconnected':
      if ($dropbox->verifyConnection()['result'] == 'connected') {
        delete_transient('bmip_dropbox_issue');
        return;
      }
      $message = sprintf(
        __('There was an error authenticating your Dropbox account. Please click %shere%s to re-authenticate, or click %shere%s to disable Dropbox as an external storage option.', 'backup-backup'),
        '<a href="javascript:document.getElementById(\'bmip-dropbox-issues-dismiss\').click();document.getElementById(\'dropbox-connect-btn\').click();">',
        '</a>',
        '<a href="javascript:document.getElementById(\'bmip-dropbox-issues-dismiss\').click();document.getElementById(\'bmi-pro-storage-dropbox-toggle\').checked=false;document.querySelector(\'#storage-options .save-btn\').click(); setTimeout(()=>{window.location.reload()}, 500);">',
        '</a>'
      );
      break;
    case 'not_enough_memory':
      if (BMP::getAvailableMemoryInBytes() >= 16 * 1024 * 1024) {
        delete_transient('bmip_dropbox_issue');
        return;
      }

      $message = sprintf(
        __('There is no enough free memory to upload the backup to Dropbox. Dropbox API requires at least 16MB of memory. Please increase the memory limit in your server configuration. Plugin will try again in %s', 'backup-backup'),
        $timeToRetry
      );
      break;
      case 'rate_limit':
        $message = sprintf(
            __('The Dropbox API rate limit has been exceeded, which means the plugin cannot proceed with the current operation at this time. The plugin will automatically attempt to retry the process in %s. Please be patient.', 'backup-backup'),
            $timeToRetry
        );
        break;
      case 'forbidden': // HAVE NOT TESTED
        $message = sprintf(
            __('The plugin does not have permission to access the Dropbox API. Please re-authenticate the plugin with Dropbox to resolve this issue. The plugin will automatically attempt to retry the process in %s.', 'backup-backup'),
            $timeToRetry
        );
        break;
      case 'insufficient_space':
        $spaceUsage = $dropbox->getSpaceUsage();
        if ($spaceUsage === false) {
          $message = sprintf(
            __('Your Dropbox account doesn’t have enough space to upload the backup. The plugin will automatically retry in %s.', 'backup-backup'),
            $timeToRetry
          );
          break;
        }
        $usagePrecentages = $spaceUsage['used'] / $spaceUsage['allocation']['allocated'] * 100;

        $requiredSpace = get_option('bmip_dropbox_required_space', 0);
        $requiredSpace = intval($requiredSpace);
        $availableSpace = $spaceUsage['allocation']['allocated'] - $spaceUsage['used'];
    
        if ($availableSpace >= $requiredSpace) {
          delete_transient('bmip_dropbox_issue');
          return;
        }    

        $message = sprintf(
          __('Your Dropbox account doesn’t have enough space to upload the backup. You’ve used %s out of %s (%s). The plugin needs %s of free space to complete the upload. It will automatically retry in %s.', 'backup-backup'),
          BMP::humanSize($spaceUsage['used']),
          BMP::humanSize($spaceUsage['allocation']['allocated']),
          number_format($usagePrecentages) . '%',
          BMP::humanSize($requiredSpace),
          $timeToRetry
        );
        break;
      default:
        return;
  }

  if (!isset($message) || get_option('bmip_dropbox_dismiss_issue', false)) return;
?>


<div class="error-noticer" id="dropbox-issues">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('We have some error regarding most recent backup upload process.', 'backup-backup'); ?>
      </div>
      <div class="right hoverable">
        <span class="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-backup'); ?>" data-collapse="<?php _e('Collapse', 'backup-backup'); ?>">
          <?php _e('Expand', 'backup-backup'); ?>
        </span> |
        <span id="bmip-dropbox-issues-dismiss">
          <?php _e('Dismiss', 'backup-backup'); ?>
        </span>
      </div>
    </div>
  </div>
  <div class="error-body">
    <?php echo $message; ?>
  </div>
</div>