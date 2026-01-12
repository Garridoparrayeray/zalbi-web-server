<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) exit;

$clouds = [];
$clouds["BACKUPBLISS"] = [ "name" => "BackupBliss", "icon" => "backupbliss-mono.svg"];
$clouds["GDRIVE"] = [ "name" => "Google Drive", "icon" => "google-drive-mono.svg" ];
$clouds["ONEDRIVE"] = [ "name" => "OneDrive", "icon" => "one-drive-mono.svg" ];
$clouds["DROPBOX"] = [ "name" => "Dropbox", "icon" => "dropbox-mono.svg" ];
$clouds["FTP"] = [ "name" => "FTP", "icon" => "ftp-mono.svg" ];
$clouds["AWS"] = [ "name" => "Amazon S3", "icon" => "amazon-s3-mono.svg" ];
$clouds["WASABI"] = [ "name" => "Wasabi", "icon" => "wasabi-s3-mono.svg" ];
$clouds["SFTP"] = [ "name" => "SFTP", "icon" => "sftp-mono.svg" ];


foreach ($clouds as $cloudKey => $cloudDetail) {
  if (!defined('BMI_PRO_INC') && !in_array($cloudKey, ["BACKUPBLISS", "DROPBOX", "GDRIVE", "FTP", "AWS", "WASABI"])) {
    $clouds[$cloudKey]["tooltip"] = __('%sNever lose a backup by also saving it on ' . $cloudDetail["name"] . '!%s%sUpgrade to %sPremium%s today%s%s%sWe made it really affordable!%s', 'backup-backup');
    $clouds[$cloudKey]["tooltip"] = sprintf($clouds[$cloudKey]["tooltip"], '<div class="bmi-center-text">', '<br>', '<a href="' . BMI_AUTHOR_URI . '" target="_blank">', '<span class="bmi-premium-bg-stars">', '</span>', '</a>', '<br>', '<b>', '</b>', '</div>');
  }
  $clouds[$cloudKey]["enabled"] = Dashboard\bmi_get_config('STORAGE::EXTERNAL::' . $cloudKey); //Make sure cloudNames you add has proper naming structure which matches the config name
}

$clouds["BACKUPBLISS"]["enabled"] = true; //BB Storage always enabled

?>

<table>
  <tr class="br_tr_template">
    <td>
      <label class="br_label" for="">
        <input class="br_checkbox" id="" type="checkbox">
        <span class="br_date">---</span>
      </label>
    </td>
    <td class="br_name tooltip-html" tooltip="example.com" data-top="5">---</td>
    <td class="br_size">---</td>
    <td class="br_stroage center">
      <div class="br_wrapper_storage_grid">
        <div class="storage-icons-container">

          <svg class="list-storage-img strg-local tooltip" tooltip="<?php _e('Local Storage', 'backup-backup') ?>"
            data-top="5">
            <use xlink:href="<?php echo $this->get_asset('images', 'local-server-2.svg#img') ?>"></use>
          </svg>

          <?php foreach ($clouds as $cloudKey => $cloudDetail):
            $isEnabled = ($cloudDetail["enabled"] === true || $cloudDetail["enabled"] === 'true');
            $hiddenStyle = $isEnabled ? '' : ' style="display: none;"';
            ?>
            <?php //Make sure again the class name you define .strg- conforms with the strucuture as implemented above. ?>
            <svg class="list-storage-img strg-<?php echo strtolower($cloudKey) ?> tooltip-html"
              tooltip="<?php echo esc_attr(isset($cloudDetail["tooltip"]) ? $cloudDetail["tooltip"] : $cloudDetail["name"]); ?>"
              data-top="5" <?php echo $hiddenStyle; ?>>
              <use xlink:href="<?php echo $this->get_asset('images', $cloudDetail["icon"] . '#img') ?>"></use>
            </svg>
          <?php endforeach; ?>

        </div>

        <div class="storage-status-indicators">

          <!-- <svg class="list-storage-img strg-suc tooltip" tooltip="<?php _e('Backup is stored on: Cloud & Local Storage.', 'backup-backup') ?>" data-top="5">
          <use xlink:href="<?php echo $this->get_asset('images', 'list-success.svg#img') ?>"></use>
        </svg> -->

          <svg class="list-storage-img strg-warn img-red tooltip"
            tooltip="<?php _e('There was an error during upload to: Cloud.', 'backup-backup') ?>" data-top="5"
            style="display: none;">
            <use xlink:href="<?php echo $this->get_asset('images', 'list-warning.svg#img') ?>"></use>
          </svg>

          <svg class="list-storage-img strg-ong ongoing tooltip"
            tooltip="<?php _e('Upload to Cloud in progress:', 'backup-backup') ?>" data-top="5" style="display: none;">
            <use xlink:href="<?php echo $this->get_asset('images', 'list-ongoing.svg#img') ?>"></use>
          </svg>

          <svg class="list-storage-img strg-wait tooltip"
            tooltip="<?php _e('Backup is queued for upload.', 'backup-backup') ?>" data-top="5" style="display: none;">
            <use xlink:href="<?php echo $this->get_asset('images', 'list-waiting.svg#img') ?>"></use>
          </svg>

        </div>
      </div>
    </td>
    <td class="center">
      <div class="brow_lock">
        <img class="tooltip bc-unlocked-btn hoverable" tooltip="<?php _e('Lock backup files', 'backup-backup') ?>"
          src="<?php echo $this->get_asset('images', 'unlocked-min.svg'); ?>" alt="image">
        <img class="tooltip bc-locked-btn hoverable" tooltip="<?php _e('Unlock backup files', 'backup-backup') ?>"
          src="<?php echo $this->get_asset('images', 'lock-min.svg'); ?>" alt="image">
      </div>
    </td>
    <td>
      <div class="brow_subactions">
        <a href="#" class="bc-download-btn hoverable nodec untab" tabindex="-1" download>
          <img class="tooltip"
            tooltip="<?php _e('Download the backup file. Click on it downloads it', 'backup-backup') ?>"
            src="<?php echo $this->get_asset('images', 'download-min.png'); ?>" alt="image">
        </a>
        <a href="#" class="tooltip bc-url-btn hoverable nodec untab bmi-tt-center" tabindex="-1" tooltip="<?php _e('Copy link to backup file for super-quick migration', 'backup-backup') ?>">
          <svg width="21px" height="22px">
            <use xlink:href="<?php echo $this->get_asset('images', 'link-min.svg#img') ?>"></use>
          </svg>
        </a>
        <a href="#" class="bc-logs-btn hoverable nodec untab" tabindex="-1" download>
          <img class="tooltip"
            tooltip="<?php _e('Download log file which was created at time of backup', 'backup-backup') ?>"
            src="<?php echo $this->get_asset('images', 'log-min.svg'); ?>" alt="image">
        </a>
      </div>
    </td>
    <td>
      <div class="stg-restore-btn hoverable tooltip-html"
        tooltip="<?php _e('Spin up a staging site with this backup.<br />Click on it to learn more.', 'backup-backup') ?>">
        <img src="<?php echo $this->get_asset('images', 'stg-restore-btn.svg'); ?>" width="12px" alt="image">
        <?php _e('Staging', 'backup-backup') ?>
      </div>
      <div class="restore-btn hoverable tooltip"
        tooltip="<?php _e('Restore this backup on this site', 'backup-backup') ?>">
        <img src="<?php echo $this->get_asset('images', 'restore-min.svg'); ?>" width="12px" alt="image">
        <?php _e('Restore', 'backup-backup') ?>
      </div>
    </td>
    <td><img class="tooltip bc-remove-btn hoverable" tooltip="<?php _e('Delete this backup', 'backup-backup') ?>"
        src="<?php echo $this->get_asset('images', 'red-close-min.svg'); ?>" alt="image"></td>
  </tr>
</table>