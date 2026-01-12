<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  $close = __("%s1Close%s2", 'backup-backup');
    $close = str_replace(
        ['%s1', '%s2'],
        ['<a href="#" class="site-reloader">', '</a>'],
        $close
    );

?>

<div class="bmi-modal bmi-modal" id="supportive-restore-success-cont-modal">

  <div class="bmi-modal-wrapper no-hpad" style="max-width: 570px; max-width: min(570px, 80vw)">
    <a href="#" class="bmi-modal-close">×</a>

    <div class="bmi-modal-content center">


      <div class="mm60 f30 bold black mtll"><?php _e('Next steps', 'backup-backup') ?></div>

      <div class="mtll f15 lh25 center block ">
        <?php _e("(All optional, but always welcome, hehe)", 'backup-backup'); ?><br>
      </div>

      <div class="btns-container">
        <div class="rate-btn">
          <a href="https://wordpress.org/support/plugin/backup-backup/reviews/#new-post" target="_blank" class="btn lime">
            <div class="flex nowrap flexcenter">
              <div class="fcentr">
                <img class="center block inline" src="<?php echo $this->get_asset('images', 'thumb.png'); ?>" alt="trash">
              </div>
              <div class="fbcont lh20">
                <span class="fbhead semibold"><?php _e("Give us a nice rating", 'backup-backup'); ?></span>
                <?php _e("…so that others discover our", 'backup-backup'); ?>
                <?php _e("plugin & benefit from it too.", 'backup-backup'); ?>
              </div>
            </div>
          </a>
        </div>
        <?php if (!defined('BMI_BACKUP_PRO') || BMI_BACKUP_PRO != 1 ): ?>
        <div class="get-premium-btn">
          <a href="<?php echo BMI_AUTHOR_URI; ?>" target="_blank" class="btn yellow">
            <div class="flex nowrap flexcenter">
              <div class="fcentr">
                <img class="center block inline" src="<?php echo $this->get_asset('images', 'crown-bg.png'); ?>" alt="trash">
              </div>
              <div class="fbcont lh20">
                <span class="fbhead semibold"><?php _e("Get our Premium plugin", 'backup-backup'); ?></span>
                <?php _e("…to benefit from many cool features & support.", 'backup-backup'); ?>
              </div>

            </div>
          </a>
        </div>
        <?php endif; ?>
        <?php if (!bmi_get_config('CRON:ENABLED')): ?>
        <div class="enable-cron">
            <a href="#!" class="btn site-reloader auto-backup-switch" id="weekly-auto-backup-switch">
                <div class="flex nowrap flexcenter">
                    <div class="fcentr">
                        <img class="center block inline" src="<?php echo $this->get_asset('images', 'weekly-schedule-icon.svg'); ?>" alt="weekly-schedule-icon">
                    </div>
                    <div class="fbcont lh20">
                        <span class="fbhead semibold"><?php _e("Switch on weekly backups", 'backup-backup'); ?></span>
                        <?php _e("…to keep your files safe!", 'backup-backup'); ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
      </div>

        <div class="mbl f15 lh25 center block ">
            <?php echo $close; ?>
        </div>


      

    </div>
  </div>

</div>
