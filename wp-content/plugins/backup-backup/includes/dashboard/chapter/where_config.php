<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  $preorder = BMI_AUTHOR_URI;
  $askMoreESText = sprintf(
    __('Need another external storage option? Please %sTell us%s!', 'backup-backup'),
    '<a href="mailto:' . BMI_SUPPORT_EMAIL .'?subject=Suggestion for new storage option&body=Please tell us which external storage option you need" target="_blank" class="secondary hoverable nodec">',
    '</a>'
  );

?>

<div class="mm mt mbl f20">
  <?php _e("Select all the storage options you want to use:", 'backup-backup'); ?>
</div>

<div class="mm60">
  <div class="mm tilo">

    <div class="tab2">

      <!-- Locally start -->
    	<div class="tab2-item d-flex jst-sb ia-center activeList">
    		<div class="d-flex ia-center">
          <img src="<?php echo $this->get_asset('images', '002-monitor-white.svg') ?>" alt="logo" class="tab2-img">
          <span class="ml25">
            <span class="title_whereStored"><?php _e("Locally", 'backup-backup'); ?></span>
            <?php _e("(on this web server)", 'backup-backup'); ?>
          </span>
        </div>
    		<div class="ia-center">
    			<!-- <div class="b2 bmi-switch"><input type="checkbox" checked class="checkbox storage-checkbox" data-toggle="storage-locally-row">
    				<div class="bmi-knobs"><span></span></div>
    				<div class="bmi-layer_str"></div>
    			</div> -->
    		</div>
    	</div>
    	<div class="bg_grey" id="storage-locally-row">
    		<div class="container-40 lh30 pt30 pb30">
    			<div class="d-flex">
    				<div class="w270" style="margin-top: 23px;"><span><?php _e("Backup directory path:", 'backup-backup'); ?></span></div>
    				<div>
    					<div class="w100 pos-r local-backup-path-wrapper"><input type="text" id="bmi_path_storage_default" placeholder="<?php _e("Enter directory path", 'backup-backup'); ?>" class="input-locally_web_server" value="<?php echo sanitize_text_field(bmi_get_config('STORAGE::LOCAL::PATH')); ?>" autocomplete="off">
                <span class="backups-suffix" id="local-backups-suffix"> /backups</span>
    						<!---->
    					</div>
    					<div class="mt10"><span>
    							<?php _e("That’s where your local backups will be stored. If you picked external storage this folder will also be used (to store your backup temporarily, until it is uploaded to the external storage).", 'backup-backup'); ?>
    						</span></div>
    				</div>
    			</div>
    			<div class="d-flex">
    				<div class="w270" style="margin-top: 23px;"><span><?php _e("Accessible via direct link?", 'backup-backup'); ?></span></div>
    				<div>
    					<div class="w100">
    						<div class="d-flex mr60 ia-center" style="margin-top: 23px;">

                  <label class="container-radio">
                    <?php _e("No", 'backup-backup'); ?>
    								<input type="radio" name="radioAccessViaLink" value="false"<?php echo (bmi_get_config('STORAGE::DIRECT::URL') === 'false')?' checked':'' ?>>
                    <span class="checkmark-radio"></span>
                  </label>

                  <label class="container-radio ml25">
                    <?php _e("Yes", 'backup-backup'); ?>
    								<input type="radio" name="radioAccessViaLink" value="true"<?php echo (bmi_get_config('STORAGE::DIRECT::URL') === 'true')?' checked':'' ?>>
                    <span class="checkmark-radio"></span>
                  </label>

                </div>
    					</div>
    					<div class="mt10">
                <span>
    							<?php _e('Select “Yes” if you want your (manually created) backups to be available via a direct link. This makes migration from one site to another super-fast.', 'backup-backup'); ?>
    						</span>
              </div>
    				</div>
    			</div>
    		</div>
    	</div>

      <?php include BMI_INCLUDES . '/bodies/storage/backupbliss.php'; ?>

      <?php require_once BMI_INCLUDES . '/bodies/storage/dropbox.php'; ?>

      <?php require_once BMI_INCLUDES . '/bodies/storage/gdrive.php'; ?>

      <?php require_once BMI_INCLUDES . '/bodies/storage/ftp.php'; ?>

      <?php require_once BMI_INCLUDES . '/bodies/storage/aws.php'; ?>

      <?php require_once BMI_INCLUDES . '/bodies/storage/wasabi.php'; ?>

      <?php
        if (has_action('bmi_pro_google_drive_template')) {
          do_action('bmi_pro_google_drive_template');
        }

        if (has_action('bmi_pro_dropbox_template')) {
          do_action('bmi_pro_dropbox_template');
        }
      ?>

      <?php
        if (has_action('bmi_pro_one_drive_template')) {
          do_action('bmi_pro_one_drive_template');
        } else {
      ?>
      <div class="tab2-item">
        <div class="already_ready"></div>
        <div class="bg_clock_day2">
          <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="crown" class="crown_img" height="30px" width="30px">
          <?php echo BMI_ALREADY_IN_PRO; ?>
        </div>
        <div class="d-flex ia-center">
          <img src="<?php echo $this->get_asset('images', 'one-drive.svg') ?>" alt="logo" class="tab2-img"> <span class="ml25 title_whereStored">OneDrive</span>
          <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="logo" class="crown2">
        </div>
        <div class="ia-center">
          <div class="b2 bmi-switch"><input type="checkbox" disabled="disabled" class="checkbox">
            <div class="bmi-knobs"><span></span></div>
            <div class="bmi-layer_str"></div>
          </div>
        </div>
      </div>
      <?php } ?>

      <?php
      if (has_action('bmi_pro_sftp_template')) {

          do_action('bmi_pro_sftp_template');
      } else {
          ?>
          <div class="tab2-item">
              <div class="already_ready"></div>
              <div class="bg_clock_day2">
                <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="crown" class="crown_img" height="30px" width="30px">
                <?php echo BMI_ALREADY_IN_PRO; ?>
              </div>
              <div class="d-flex ia-center">
                <img src="<?php echo $this->get_asset('images', 'sftp-scp.svg') ?>" alt="logo" class="tab2-img"> <span class="ml25 title_whereStored">SFTP</span>
                <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="logo" class="crown2">
              </div>
              <div class="ia-center">
                  <div class="b2 bmi-switch"><input type="checkbox" disabled="disabled" class="checkbox">
                      <div class="bmi-knobs"><span></span></div>
                      <div class="bmi-layer_str"></div>
                  </div>
              </div>
          </div>
      <?php } ?>

      <?php
        if (has_action('bmi_pro_ftp_template')) {
          do_action('bmi_pro_ftp_template');
        }
        
        if (has_action('bmi_pro_aws_s3_template')) {
          do_action('bmi_pro_aws_s3_template');
        }

        if (has_action('bmi_pro_wasabi_template')) {
          do_action('bmi_pro_wasabi_template');
        }
      ?>
    </div>
    <div class="center f16" style="margin-top: 10px;">
      <span><?php echo $askMoreESText; ?></span>
    </div>
  </div>
</div>

<?php include BMI_INCLUDES . '/dashboard/chapter/save-button.php'; ?>
