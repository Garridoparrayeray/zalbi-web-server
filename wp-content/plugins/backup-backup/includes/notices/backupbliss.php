<?php
// Exit on direct access
if (!defined('ABSPATH')) exit;

use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\BMI_Logger as Logger;

require_once BMI_INCLUDES . '/external/backupbliss.php';
$backupbliss = new BackupBliss();
$notices = $backupbliss->getNotices();
$upload_issue_notice = $backupbliss->getNotice("upload_issue_space");

if ($upload_issue_notice) {
    //If it's a space issue we'll already show red warning on top so we'll hide it from notices
    $backupbliss->hideNotice("upload_issue_space");
}

$notices = array_filter($notices, function($notice) use ($backupbliss) {
  return $backupbliss->canShowNotice($notice);
}, ARRAY_FILTER_USE_KEY);


if (count($notices) == 0)
    return;
?>

  <div class="error-noticer" id="backupbliss-issues">
    <div class="error-header">
      <div class="cf">
        <div class="left">
          <?php _e('We have some issue(s) regarding BackupBliss.', 'backup-backup'); ?>
        </div>
        <div class="right hoverable">
          <span class="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-backup'); ?>" data-collapse="<?php _e('Collapse', 'backup-backup'); ?>">
            <?php _e('Expand', 'backup-backup'); ?>
          </span> |
          <span id="bmi-error-dismiss" issue-type="backupbliss" onclick="document.getElementById('backupbliss-issues').remove()">
            <?php _e('Dismiss', 'backup-backup'); ?>
          </span>
        </div>
      </div>
    </div>
    <div class="error-body">
      <?php
        echo implode("<br /><br />", $notices);
      ?>
    </div>
  </div>