<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;



?>

<div class="bmi-modal bmi-modal-no-close" id="restore-parts-modal">
  <div class="bmi-modal-wrapper no-hpad" style="max-width: 780px; max-width: min(780px, 80vw);">
    <span class="bmi-modal-back">
    </span>
    <a href="#" class="bmi-modal-close">×</a>
    <div class="bmi-modal-content center">
        <div class="f26 bold black modal-title">
            <?php _e('Restore Backup', 'backup-backup'); ?>
        </div>
        <div class="location-container">
        </div>
        
        <div class="zip-content-table">
          <table>
            <thead>
              <tr>
                <th class="checkbox-column"></th>
                <th><?php _e('Name', 'backup-backup'); ?></th>
                <th><?php _e('Date Modified', 'backup-backup'); ?></th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
        
        <div class="mm60 mtl center">
            <a href="#" class="btn max280" id="restore-parts">
            <div class="text">
                <div class="f20 bold"><?php _e('Start restoring!', 'backup-backup') ?></div>
            </div>
            </a>
        </div>

    </div>
  </div>
</div>