<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<div class="bmi-modal bmi-modal-no-close" id="bb-disconnect-modal">
  <div class="bmi-modal-wrapper" style="max-width: 285px;">
  <a href="#" class="bmi-modal-close npt">×</a>
    <div class="bmi-modal-content center">
        <div class="confirm-disconnect ">
            <img src="<?php echo $this->get_asset('images', 'confirm-action.svg') ?>" alt="confirmation" class="confirm-action-img">
            <span class="f24 bold lh30"><?php _e('You’re sure you want to disconnect? ', 'backup-backup'); ?></span>
            <a href="#" class="f18 bb-disconnect-btn semibold"><?php _e('Yes, please disconnect', 'backup-backup'); ?></a>
            <a href="#" class="f18 bb-disconnect-cancel light"><?php _e('Hold on, cancel', 'backup-backup'); ?></a>
        </div>
    </div>
  </div>

</div>
