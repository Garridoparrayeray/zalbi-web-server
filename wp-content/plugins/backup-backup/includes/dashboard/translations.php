<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Load translations
  $additional_translation_pre_load = [
    __("Exclude certain tables from backups", 'backup-backup'),
    __("Currently you excluded", 'backup-backup'),
    __("Red tables", 'backup-backup'),
    __("makred with star* are part of WordPress core, exclude them only if youre 100% sure what you are doing.", 'backup-backup'),
    __("Blue tables", 'backup-backup'),
    __("are related to your WordPress instance, most of them are related to your plugins.", 'backup-backup'),
    __("If you see any black table here, it's recommended to exclude them.", 'backup-backup'),
    __("Hint: Hold shift+click to selece many tables at once.", 'backup-backup'),
    __("out of %s tables.", 'backup-backup'),
    __("In total you save %s MB.", 'backup-backup'),
    __('rows', 'backup-backup')
  ];

?>

<div class="translations" style="display:none;visibility:hidden;height:0;width:0;">
  <div id="premium-tooltip">
    <?php if (defined('BMI_PREMIUM_TOOLTIP')): ?>
    <?php echo BMI_PREMIUM_TOOLTIP; ?>
    <?php endif; ?>
  </div>
  <div id="premium-tooltip-r">
    <?php if (defined('BMI_PREMIUM_TOOLTIP_R')): ?>
    <?php echo BMI_PREMIUM_TOOLTIP_R; ?>
    <?php endif; ?>
  </div>
  <div id="premium-tooltip-pre"><b><u><?php _e('Pro feature:', 'backup-backup'); ?></u></b></div>

  <div id="bmi-success-copy">
    <?php _e('Text copied successfully', 'backup-backup') ?>
  </div>
  <div id="bmi-received-hard">
    <?php _e('Browser successfully received backup settings.', 'backup-backup') ?>
  </div>
  <div id="bmi-failed-copy">
    <?php _e('Your browser does not support copying, please copy by hand', 'backup-backup') ?>
  </div>
  <div id="bmi-abort-soon">
    <?php _e('Backup will be aborted in few seconds.', 'backup-backup') ?>
  </div>
  <div id="bmi-aborted-al">
    <?php _e('Backup process aborted.', 'backup-backup') ?>
  </div>
  <div id="bmi-stg-aborted-al">
    <?php _e('Staging process aborted.', 'backup-backup') ?>
  </div>
  <div id="bmi-downloading-remote">
    <?php _e('Downloading backup file...', 'backup-backup') ?>
  </div>
  <div id="bmi-restoring-prepare">
    <?php _e('Preparing restore process...', 'backup-backup') ?>
  </div>
  <div id="bmi-restore-require-checkmark">
    <?php _e('You have to confirm that you understand the risk.', 'backup-backup') ?>
  </div>
  <div id="bmi-upload-start">
    <?php _e('File upload started.', 'backup-backup') ?>
  </div>
  <div id="bmi-upload-error">
    <?php _e('There was an error during file upload.', 'backup-backup') ?>
  </div>
  <div id="bmi-upload-end">
    <?php _e('File has been uploaded successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-upload-wrong">
    <?php _e('File has wrong type.', 'backup-backup') ?>
  </div>
  <div id="bmi-upload-exists">
    <?php _e('File already exist in backup directory.', 'backup-backup') ?>
  </div>
  <div id="bmi-remove-success">
    <?php _e('Backup(s) removed successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-remove-error">
    <?php _e('Cannot remove backup(s) file(s) due to unknown error.', 'backup-backup') ?>
  </div>
  <div id="bmi-save-success">
    <?php _e('Configuration saved successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-save-issues">
    <?php _e('There was an issue during saving, some settings may stay unchanged.', 'backup-backup') ?>
  </div>
  <div id="bmi-no-file">
    <?php _e('Could not find this backup, it may be deleted or there was an error with getting the name.', 'backup-backup') ?>
  </div>
  <div id="bmi-unlock-success">
    <?php _e('File unlocked successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-unlock-error">
    <?php _e('Could not unlock this backup due to unknown error, please reload and try again.', 'backup-backup') ?>
  </div>
  <div id="bmi-lock-success">
    <?php _e('File locked successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-lock-error">
    <?php _e('Could not lock this backup due to unknown error, please reload and try again.', 'backup-backup') ?>
  </div>
  <div id="bmi-download-should-start">
    <?php _e('Download process should start.', 'backup-backup') ?>
  </div>
  <div id="bmi-preb-processing">
    <?php _e('Backup Migration plugin is calculating the size of your files, please try again in a few seconds.', 'backup-backup') ?>
  </div>
  <div id="bmi-no-selected">
    <?php _e('There is nothing to backup. Please select database and / or files to backup.', 'backup-backup') ?>
  </div>
  <div id="bmi-invalid-url">
    <?php _e('The URL you provided does not seems to be correct.', 'backup-backup') ?>
  </div>
  <div id="bmi-bc-ended">
    <?php _e('Backup process ended, we triggered backup list reload for your.', 'backup-backup') ?>
  </div>
  <div id="bmi-current-time">
    <?php _e('Current server time: ', 'backup-backup') ?>
  </div>
  <div id="bmi-next-cron">
    <?php _e('Next backup planned: ', 'backup-backup') ?>
  </div>
  <div id="bmi-cron-updated">
    <?php _e('Settings updated successfully', 'backup-backup') ?>
  </div>
  <div id="bmi-cron-updated-fail">
    <?php _e('Could not update CRON setting now, please check the logs.', 'backup-backup') ?>
  </div>
  <div id="bmi-making-archive">
    <?php _e("Making archive", 'backup-backup') ?>
  </div>
  <div id="bmi-email-success">
    <?php _e('Email send successfully, check mailbox.', 'backup-backup') ?>
  </div>
  <div id="bmi-email-fail">
    <?php _e("There was an error sending the email, please use additional plugins to debug it or ask your hosting administrator for help.", 'backup-backup') ?>
  </div>
  <div id="bmi-manual-locked">
    <?php _e("Manually created backups are always locked.", 'backup-backup') ?>
  </div>
  <div id="bmi-default-success">
    <?php _e("Operation finished with success.", 'backup-backup') ?>
  </div>
  <div id="bmi-default-fail">
    <?php _e("Operation failed, please try again.", 'backup-backup') ?>
  </div>
  <div id="bmi-loading-translation">
    <?php _e("Loading...", 'backup-backup') ?>
  </div>
  <div id="failed-to-stop">
    <?php _e("We could not stop the process due to some issues on your server (maybe file permissions).", 'backup-backup') ?>
  </div>
  <div id="bmi-force-stop-in-progress">
    <?php _e("Stopping the process, it will be confirmed...", 'backup-backup') ?>
  </div>
  <div id="bmi-force-stop-success">
    <?php _e("The process should be stopped now.", 'backup-backup') ?>
  </div>
  <div id="bmi-support-send-success">
    <?php _e("Logs shared successfully.", 'backup-backup') ?>
  </div>
  <div id="bmi-support-send-fail">
    <?php _e("There was an error while sharing logs for support.", 'backup-backup') ?>
  </div>
  <div id="bmi-support-send-start">
    <?php _e("Sending your logs, please wait (up to 15 seconds)...", 'backup-backup') ?>
  </div>
  <div id="bmi-backup-created-on">
    <?php _e("Backup created on site:", 'backup-backup') ?>
  </div>
  <div id="bmi-backup-original-name">
    <?php _e("Backup original name:", 'backup-backup') ?>
  </div>
  <div id="bmi-backup-file-name">
    <?php _e("Name of file on server:", 'backup-backup') ?>
  </div>
  <div id="bmi-file-disabled">
    <?php _e("You cannot backup this, it's disabled in settings.", 'backup-backup') ?>
  </div>
  <div id="bmi-backup-logs-modal-title">
    <?php _e("Backup logs", 'backup-backup') ?>
  </div>
  <div id="bmi-restore-logs-modal-title">
    <?php _e("Restore logs", 'backup-backup') ?>
  </div>
  <div id="bmi-error-modal-title">
    <?php _e("Backup creation failed", 'backup-backup') ?>
  </div>
  <div id="bmi-restore-error-modal-title">
    <?php _e("Restore failed", 'backup-backup') ?>
  </div>
  <div id="bmi-backup-downloaded">
    <?php _e('Backup downloaded successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-save-connect-s3-success">
    <?php _e('We have successfully connected to your S3 bucket.', 'backup-backup') ?>
  </div>
  <div id="bmi-download-progress-modal-title">
    <?php _e('Downloading backup file', 'backup-backup') ?>
  </div>
  <div id="bmi-download-warning">
    <?php _e('Backup file needs to be downloaded before the parts can be selected', 'backup-backup') ?>
  </div>
  <div id="bmi-restore-progress-modal-title">
    <?php _e('Restoration in progress', 'backup-backup') ?>
  </div>
  <div id="bmi-restore-progress-modal-warning">
    <?php _e('Do not close this window as long as the restoration process is ongoing', 'backup-backup'); ?>
  </div>
  <?php if (false) { ?>
  <div id="bmi-share-logs-thank-you">
    <?php _e("Thank you very much for your support!", 'backup-backup') ?>
  </div>
  <div id="bmi-staging-prepare">
    <?php _e('Preparing creation of staging site...', 'backup-backup') ?>
  </div>
  <?php } ?>
  <div id="bmi-before-core-update-backup">
    <?php _e('Core and database successfully backed up.', 'backup-backup') ?>
  </div>
  <div id="bmi-before-update-is-enabled">
    <?php echo bmi_get_config('OTHER:TRIGGER:BEFORE:UPDATES') ? 1 : 0; ?>
  </div>
  <div id="bmi-before-update-backup">
    <?php _e("Before update backup", 'backup-backup') ?>
  </div>
  <div id="creating-backup">
    <?php _e("Creating backup...", 'backup-backup') ?>
  </div>
  <div id="core-backup-in-progress">
    <?php _e("Core backup in progress...", 'backup-backup') ?>
  </div>
  <div id="theme-backup-in-progress">
    <?php _e("Theme backup in progress...", 'backup-backup') ?>
  </div>
  <div id="plugins-backup-in-progress">
    <?php _e("Plugins backup in progress...", 'backup-backup') ?>
  </div>
  <div id="backup-succeeded-update-will-start">
    <?php _e("Backup created successfully. Proceeding with update.") ?>
  </div>
  <div id="backup-failed">
    <?php _e("Backup failed: ", 'backup-backup') ?>
  </div>
  <div id="backup-failed-update-anyway">
    <?php _e("Backup failed, update anyway", 'backup-backup') ?>
  </div>
  <div id="update-anyway">
    <?php _e("Update anyway", 'backup-backup') ?>
  </div>
  <div id="error-occurred">
    <?php _e("An error occurred during the backup process. Please check ", 'backup-backup') ?>
  </div>
  <div id="bmi-dashboard">
    <?php _e("Backup Migration dashboard", 'backup-backup') ?>
  </div>
  <div id="or">
    <?php _e("or ", 'backup-backup') ?>
  </div>
  <div id="update-anyway-small">
    <?php _e("update anyway", 'backup-backup') ?>
  </div>
  <div id="proceed-without-backup">
    <?php _e("to proceed without backup", 'backup-backup') ?>
  </div>
  <div id="updating">
    <?php _e("Updating...", 'backup-backup') ?>
  </div>
  <div id="ask-if-update-anyway">
    <?php _e('Would you like to proceed with the update without a backup? %sUpdate Anyway.%s', 'backup-backup') ?>
  </div>
  <div id="bmi-save-connect-ftp">
    <?php _e('Connected to FTP server successfully.', 'backup-backup') ?>
  </div>
  <div id="bmi-save-connect-sftp">
    <?php _e('Connected to SFTP server successfully.', 'backup-backup') ?>
  </div>
  <div id="direct-link-disabled-tooltip">
    <?php _e('Enable backup accessibility via links in the plugin section “Where should the backup(s) be stored?”', 'backup-backup') ?>
  </div>
  <div id="direct-link-enabled-tooltip">
    <?php _e('Copy link to backup file for super-quick migration', 'backup-backup') ?>
  </div>




  <div id="BMI_URL_ROOT"><?php echo plugin_dir_url(BMI_ROOT_FILE); ?></div>
  <div id="BMI_BLOG_URL"><?php echo get_site_url(); ?></div>
  <div id="BMI_REV"><?php echo BMI_REV; ?></div>
  <div id="BMI_SECRET_KEY"><?php echo bmi_get_config('REQUEST:SECRET'); ?></div>
  <div id="BMI_ASSETS"><?php echo BMI_ASSETS; ?></div>
  <div><input type="text" id="bmi-support-url-translation" value="<?php echo BMI_CHAT_SUPPORT_URL ?>" hidden autocomplete="off"></div>
</div>
