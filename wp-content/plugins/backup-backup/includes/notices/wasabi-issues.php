<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\External\BMI_External_S3 as S3;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  require_once BMI_INCLUDES . '/external/s3.php';
  $s3 = new S3('wasabi');

  $s3Issue = $s3->getIssue();
  $timeToRetry = $s3Issue['retryAfter'];

  
  if (!$s3Issue['issue']) return;

  switch ($s3Issue['issue']) {
    case 'disconnected':
      if ($s3->verifyConnection()['result'] == 'connected') {
        $s3->deleteIssue();
        return;
      }
      $message = sprintf(
      __('Your authentication to Wasabi has expired or become invalid. Please re-authenticate to restore access. If the issue persists, check your credentials and token validity. You can also disable Wasabi as an external storage option by clicking %shere%s.', 'backup-backup'),
      '<a href="javascript:document.getElementById(\'bmi-error-dismiss\').click();document.getElementById(\'bmi-pro-storage-wasabi-toggle\').checked=false;document.querySelector(\'#storage-options .save-btn\').click(); setTimeout(()=>{window.location.reload()}, 500);">',
      '</a>'
      );
    
      break;
    case 'forbidden':
      $message = sprintf(
      __('The plugin has lost the required permissions to access Wasabi S3. This may be due to changes in your access policies. Please verify your IAM permissions and re-authenticate. The plugin will automatically retry in %s.', 'backup-backup'),
      $timeToRetry
      );
      
      break;
    default:
        return;
  }

  if (!isset($message) || $s3Issue['dismissed']) return;
?>


<div class="error-noticer" id="wasabi-issues">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('We have some errors regarding most recent backup upload process.', 'backup-backup'); ?>
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