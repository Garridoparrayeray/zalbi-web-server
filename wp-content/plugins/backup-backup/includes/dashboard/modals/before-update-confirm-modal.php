<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  $note = sprintf(
    __("Note: You're seeing this confirmation because you've enabled the backup before updates feature in the %sBackup & Migration%s plugin.", 'backup-backup'),
    '<a href="' . admin_url('admin.php?page=backup-migration') . '" target="_blank" class="secondary">',
    '</a>'
  );

?>

<div class="bmi-modal" id="before-update-confirm-modal">

  <div class="bmi-modal-wrapper" style="min-height: 0px; max-width: 564px; max-width: min(564px, 80vw);">
    <div class="bmi-modal-content">
      
      <div style="text-align: center;">
        <div style="margin-bottom: 24px;">
          <span class="dashicons dashicons-shield" style="font-size: 48px; width: 48px; height: 48px; color: #257671;"></span>
        </div>
        
        <h2 style="font-size: 24px; color: #1e1e1e; margin: 0 0 16px; line-height: 1.3;">
          <?php _e("Safeguard Your Site with Smart Auto-Backups", 'backup-backup'); ?>
        </h2>
        
        <p style="font-size: 16px; color: #505050; margin: 0 0 24px; line-height: 1.5;">
          <?php _e("Would you like to make an automatic backup before proceeding with the update?", 'backup-backup'); ?>
        </p>
        
        <p style="font-size: 14px; color: #666; font-style: italic; margin: 0 0 32px; line-height: 1.4;">
          <?php echo $note; ?>
        </p>
      </div>

      <div class="center mtl">
        <div class="cf inline">
          <div class="left inline mr50">
            <a href="#" class="btn bold mm" id="before-update-backup-confirm"><?php _e("Yes", 'backup-backup'); ?></a>
          </div>
          <div class="left inline">
            <a href="#" class="btn bold mm grey nodec bmi-modal-closer" id="before-update-backup-cancel"><?php _e("No", 'backup-backup'); ?></a>
          </div>
        </div>
      </div>

    </div>

  </div>
