<?php

  // Namespace
  namespace BMI\Plugin;
  use BMI\Plugin\Dashboard as Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Google drive template
  add_action('bmi_pro_google_drive_template', function () {
    $clinet_token = get_option('bmi_pro_gd_client_id', false);
    $site_token = get_option('bmi_pro_gd_token', false);
    $shouldBeConnected = false;
    if (is_string($site_token) && is_string($clinet_token)) {
      $shouldBeConnected = true;
    }

    $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE');
    if ($isEnabled === true || $isEnabled === 'true') {
      $isEnabled = ' checked';
    } else $isEnabled = '';

    ?>
      <!-- External: Google drive -->
      <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">
        <div class="d-flex ia-center">
          <img src="<?php echo $this->get_asset('images', 'google-drive.svg') ?>" alt="logo" class="tab2-img">
          <span class="ml25 d-flex ia-center">
            <span class="title_whereStored"><?php _e("Google Drive", 'backup-backup'); ?></span>
          </span>
        </div>
        <div class="ia-center">
          <div class="b2 bmi-switch"><input type="checkbox" class="checkbox storage-checkbox"<?php echo $isEnabled; ?> data-toggle="storage-gdrive-row" id="bmi-pro-storage-gdrive-toggle">
            <div class="bmi-knobs"><span></span></div>
            <div class="bmi-layer_str"></div>
          </div>
        </div>
      </div>
      <div class="bg_grey storage_target" id="storage-gdrive-row"<?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <?php
        $disabled_functions = explode(',', ini_get('disable_functions'));
        $vA = !in_array('curl_exec', $disabled_functions);
        $vB = !in_array('curl_init', $disabled_functions);

        if (function_exists('curl_version') && function_exists('curl_exec') && function_exists('curl_init') && $vA && $vB) {
          ?>

          <div class="container-40 lh30 pt30">
            <div class="d-flex">
      				<div class="w270" style="margin-top: 23px;"><span>Backup directory path:</span></div>
      				<div>
      					<div class="w100 pos-r">
                  <input id="bmip-googledrive-path" class="input-googledrive_storage" type="text" value="<?php echo sanitize_text_field(Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE::DIRNAME')); ?>" placeholder="Directory_Name_Of_My_Backups_In_GDrive" autocomplete="off">
      					</div>
      					<div class="mt10">
                  <span>
      						  <?php _e('You can set individual directory name for this website. Allowed characters: 0-9, A-z, _, -', 'backup-backup'); ?><br />
      						  <?php _e('If you have any existing backups, will not be moved automatically, they will require you to move them manually or upload once again.', 'backup-backup'); ?><br />
                    <?php _e('You have to allow our plugin to create the directory, otherwise our plugin cannot access files within that directory.', 'backup-backup'); ?>
                  </span>
                </div>
      				</div>
      			</div>
          </div>

          <div id="gdrive-unauthenticated-box" class="container-40 lh30 pt30 pb30" <?php echo (($shouldBeConnected) ? 'style="display: none;"' : ''); ?>>
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;"><span id="gdrive-not-authed-content-box">Current status:&nbsp;<b>Inactive</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                            <a href="#" id="gdrive-connect-btn" class="btn"><?php _e("Connect", 'backup-backup'); ?></a>
      					</div>
      				</div>
      			</div>
            <div class="d-flex">
              <blockquote class="bmi-gdrive-info">
                The plugin only asks for the permissions it <i>really</i> needs in order for the feature to work. It can only see or edit the files itself created, <b>no other</b>. If that doesn’t put your mind at ease, you can also always set up a new (empty) Google account (<a href="https://accounts.google.com/signup" target="_blank">here</a>) and then provide access to that.
              </blockquote>
            </div>
          </div>
          <div id="gdrive-authenticated-box" class="container-40 lh30 pt30 pb30" <?php echo ((!$shouldBeConnected) ? 'style="display: none;"' : ''); ?>>

            <div class="d-flex">
      				<div class="w270" style="margin-top: 11px;"><span id="gdrive-authed-content-box">Current status:&nbsp;<b>Active</b></span></div>
      				<div>
      					<div class="w100 pos-r">
                  <a href="#" id="gdrive-disconnect-btn" class="btn"><?php _e("Disconnect", 'backup-backup'); ?></a>
      					</div>
      				</div>
      			</div>

          </div>
          <?php
        } else {
          ?>
          <div class="container-40 lh30 pt30 pb30">
            <div class="center">
              It seem like you don't have cURL extension (PHP module) installed on your server.<br />
              Without this module it's impossible to upload backups to Google Drive.<br />
              If you wish to use this feature, please enable cURL module.
            </div>
          </div>
          <?php
        }
        ?>
      </div>
    <?php
  });
