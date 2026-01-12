<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Premium
  $sellcodes = BMI_AUTHOR_URI;
  // $tooltip = str_replace('"', "'", );
  $pros = defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO == 1;
?>

<div class="bmi-modal" id="pre-restore-modal">

  <div class="bmi-modal-wrapper no-hpad no-vpad" style="max-width: 900px; max-width: min(900px, 80vw)">
    <a href="#" class="bmi-modal-close">×</a>
    <div class="bmi-modal-content">

      <div class="prenotices">

        <div class="prenotice red top">
          <div class="text bold">
            <?php _e('All existing folders, files & databases on this site will be overwritten and destroyed for good. *', 'backup-backup') ?> 
          </div>
        </div>

      </div>

      <div class="mm60 center f20 mbl">
        <label for="restore-ok">
          <input type="checkbox" id="restore-ok" />
          <span><?php _e('Yes, I understand that. I am sound in mind.', 'backup-backup') ?></span>
        </label>
      </div>

      <div class="mm60 center mbl">
        <a href="#" class="btn max280" id="restore-start-sure">
          <div class="text">
            <div class="f20 bold"><?php _e('Start restoring!', 'backup-backup') ?></div>
          </div>
        </a>
      </div>

      <div class="mm60 center mbl f18">
        <div class="center inline premium-<?php bmi_pro_features($pros, true, __("Recover only what you<br>need from your backup", 'backup-backup')); ?>">
          <div class="premium premium-img restore-parts under">
            <?php _e('Only want to restore parts of the backup?', 'backup-backup') ?>
          </div>
        </div>
      </div>

      <div class="mm60 f18 center mbll">
        <a href="#" class="bmi-modal-closer text-muted" data-close="pre-restore-modal"><?php _e('Close window & do not restore', 'backup-backup') ?></a>
      </div>

      <div class="mm60 center mb">
        <?php _e('* Only those folders, files & database will be replaced which also exist in the backup file.', 'backup-backup') ?>
      </div>

    </div>
  </div>

</div>
