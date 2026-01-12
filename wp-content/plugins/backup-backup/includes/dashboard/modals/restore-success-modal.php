<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  $chooseBackupInterval = __("If you prefer a different interval, you can set it %s1here%s2.", 'backup-backup');
  $closeMessage = __("Or %s1don't switch on automatic backups and close%s2.", 'backup-backup');

  $chooseBackupInterval = str_replace(
    ['%s1', '%s2'],
    ['<a href="#!" class="site-reloader" id="choose-auto-backup-interval">', '</a>'],
    $chooseBackupInterval
  );

  $closeMessage = str_replace(
    ['%s1', '%s2'],
    ['<a href="#" class="site-reloader">', '</a>'],
    $closeMessage
  );

  $reviewedBefore = get_option('bmi_review_clicked', false);

?>

<div class="bmi-modal bmi-modal-no-close" id="restore-success-modal">

  <div class="bmi-modal-wrapper no-hpad" style="max-width: 900px; max-width: min(900px, 80vw)">
    <div class="bmi-modal-content center">

      <img class="mtl" src="<?php echo $this->get_asset('images', 'happy-smile.png'); ?>" alt="success">
      <div class="mm60 f35 bold black mbl mtll"><?php _e('Restore successful!', 'backup-backup') ?></div>

      <div class="mbl f20 lh30">
        <?php _e("Liked how easy it was? Then PLEASE support the further", 'backup-backup'); ?><br>
        <?php _e("development of our plugins by doing the following:", 'backup-backup'); ?>
      </div>

      <?php if (!defined('BMI_BACKUP_PRO')): ?>
      <div class="cf mb inline center block suc-buttns">
        <div class="left a1">
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
        <div class="left a2">
          <a href="<?php echo BMI_AUTHOR_URI; ?>" target="_blank" class="btn">
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
      </div>

      <?php else: ?>
      <div class="bmi-ask-for-review" style="<?php echo $reviewedBefore ? 'display: none;' : ''; ?>">
        <div class="cf mm60">
          <div class="left bmi-positive-wrapper">
            <img src="<?php echo $this->get_asset('images', 'big-thumb-up.svg'); ?>" alt="positive-thumb-up" class="bmi-positive-thumb">
          </div>
          <div class="left bmi-thumb-info">
            <div class="f16 lh30 mm30 mtll mbll">
              <?php _e("Like how easy it was? Then <b>PLEASE</b> give us a nice rating so that others discover out plugin & benefit from it too. Thank you!!", 'backup-backup'); ?>
            </div>
            <div class="cf lh60 mm30">
              <div class="left">
                <a href="https://wordpress.org/support/plugin/backup-backup/reviews/#new-post" target="_blank" class="btn inline btn-pad mm30 bmi-review-btn">
                  <div class="text">
                    <div class="f14 semibold"><?php _e('Sounds fair, let me give a rating', 'backup-backup'); ?></div>
                  </div>
                </a>
              </div>
              <div class="right relative">
                <a href="#!" class="nodec secondary semibold">
                  <span class="tooltip hoverable info-cursor f14" tooltip="<?php echo $ctl; ?>">
                    <?php _e("Trouble logging in?", 'backup-backup'); ?>
                    <span class="bmi-info-icon"></span>
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="mb f28 secondary center semibold">
        <?php _e("Thank you!!", 'backup-backup'); ?>
      </div>

      <?php if(bmi_get_config('CRON:ENABLED')) :?>

      <div class="center mbl">
        <a href="#" class="btn width50 f22 inline grey bold nodec site-reloader">
          <?php _e("Ok, close", 'backup-backup'); ?>
        </a>
      </div>

      <div class="center f17 mbl">
        <a href="#!" class="download-restore-log-url" download="restoration_logs.txt">
          <?php _e("Download the log", 'backup-backup'); ?></a> <?php _e("of the restoration process", 'backup-backup'); ?>
      </div>

      <?php else : ?>

      <div class="mm30">
        <section class="auto-backup-reminder" >

          <div class="pt30">

            <article class="box">

              <div class="f20 lh30 text">
                <?php _e("Next, <b>keep your files</b> safe by enabling automatic backups:", 'backup-backup'); ?>
              </div>

              <div class="auto-backup-switch site-reloader" id="weekly-auto-backup-switch">

                <span class="text bold">
                  <?php _e("Switch on weekly automatic backups", 'backup-backup'); ?>
                </span>

                <div class="circle-box" >
                  <span class="right-arrow"></span>
                </div>

              </div>

            </article>

            <div class="text f16">
              <?php echo $chooseBackupInterval ?>
            </div>

            <div class="small-text f15">
              <?php echo $closeMessage ?>
            </div>

          </div>

        </section>
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>
