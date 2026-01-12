<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;


  $ctl = __("Your account on Wordpress.org (where you open a new support thread) is different to the one you login to your WordPress dashboard (where you are now). If you don’t have a WordPress.org account yet, please sign up at the top right on the Support Forum page, and then scroll down on that page . It only takes a minute :) Thank you!", 'backup-backup');
  $forumText= __("<b>PLEASE help us to improve the plugin</b> by opening a new thread %s1in the forum%s2 and copy the following logs:", 'backup-backup');
  $forumText = str_replace(
    ['%s1', '%s2'],
    ['<a href="https://wordpress.org/support/plugin/backup-backup/" target="_blank">', '</a>'],
    $forumText
  );

?>

<div class="bmi-modal bmi-modal-no-close" id="supportive-restore-success-modal">

    <div class="bmi-modal-wrapper no-hpad" style="max-width: 775px; max-width: min(775px, 80vw)">
        <div class="bmi-modal-content center">

            <img class="mtl" src="<?php echo $this->get_asset('images', 'happy-smile.png'); ?>" alt="success">
            <div class="mm60 f35 bold black mbl mtll"><?php _e('Restore successful!', 'backup-backup') ?></div>

            <div class="mbl f18 lh25 mm60 align-left mtl">
                <?php _e("The restore was 100% successful, however we noticed some optimization potential (to enhance the speed).", 'backup-backup'); ?><br>
            </div>
            <div class="mbl f18 lh25 mm60 align-left">
                <?php echo $forumText ?>
            </div>

            <div class="log-wrapper">
                <div class="restore-log-wrapper">
                    <textarea id="restore-log" readonly></textarea>
                </div>

                <div class="copy-logs-wrapper">
                    <a href="#" class="btn inline btn-pad bmi-copper othersec mm30 restore-log" data-copy="restore-log">
                        <div class="f15 semibold"><?php _e('Copy logs', 'backup-backup') ?></div>
                    </a>
                </div>
                <div class="trouble-logging-into-forum align-left">
                    <span class="tooltip hoverable info-cursor f14" tooltip="<?php echo $ctl; ?>">
                        <a href="https://wordpress.org/support/plugin/backup-backup/" target="_blank">
                            <?php _e("Trouble logging<br>into the forum?", 'backup-backup'); ?>
                        </a>
                    </span>
                </div>

            </div>


            <div>
                <div class="mm mtl mbl lh30">
                    <a href="#" class="btn shared-log-after-restore"><?php _e("Done", 'backup-backup'); ?></a>
                </div>
                <a class="lh22 f15 skip-share-logs-after-restore" href="#"><?php _e("Skip", 'backup-backup'); ?></a>

            </div>
        </div>

    </div>
</div>