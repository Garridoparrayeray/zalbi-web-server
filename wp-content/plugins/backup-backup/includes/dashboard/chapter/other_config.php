<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\Checker\System_Info;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }
  
  $pros = false;
  if (defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO == 1) {
    $pros = true;
  }

  // Tooltips
  $deinstalled_info = __('This will be triggered on plugin removal via WordPress plugins tab', 'backup-backup');
  $experimental_info = __('It will change some fundamental logics of the plugin', 'backup-backup');
  $experimental_info_1 = __('Use this option if you have full access to your server and you know how to make basic configuration of the server. Wrong configuration may give you hick-ups without error due to e.g. web server server timeout (for small sites below 300 MB this is the best option).', 'backup-backup');
  $experimental_info_2 = __('Use this option before the third one. It should work fine on SSD/NVMe hosting, even for large backups — but it may still time out if you are running on a slow drive with high I/O.', 'backup-backup');
  $experimental_info_3 = __('This option will require you to not close the backup window since it will use your connection to keep the backup in parts, it will disable automatic backups. Use this only if all of the above does not work. Recommended for huge sites +100k files / 5+ GB.', 'backup-backup');
  $db_query_info = __('Lower value means slower process but more stable restore, higher value means quicker backup and restore but it may be unstable (depends on database server performance). Default value: 300.', 'backup-backup');
  $sqlsplitting = __('This will split the SQL files (before migration or restore) into parts, which should make the process more stable and also allows to track the progress more precisely.', 'backup-backup');
  $deprecatedsinglefile = __('It will force to use V1 engine (first export function of this plugin), it is usually much quicker but search & replace may not work well for recursively santisized data - but may be recommended for not complex sites.', 'backup-backup');
  $cleanupbeforerestore = __('Advanced details: This will remove all plugins (excluding Backup Migration) and themes before performing the migration. During migration, these files will be kept in the directory wp-content/backup-migration/clean-ups. If you want to keep them after migration, you can use the wp-config.php constant BMI_KEEP_CLEANUPS set to TRUE.', 'backup-backup');
  $disabledspacechecking = __('This option will disable validation of free space on your server i.e. if there is enough space to make the backup. Use it only when you are 100% sure that you have enough space, otherwise backup process may fail with fatal error. In corner cases, if there will not be enough space it may make your site slow or even limit functionality.', 'backup-backup');
  $dbbatching = __('This option will enable batching for database table export (backup). It will affect only non-default methods of the backup. It will significantly slow down the backup process, but it will make it much more stable.', 'backup-backup');
  
  $basicMessageHidePromos = __('%sHide the carousel at the bottom of the plugin page, and all plugin’s messages in the Dashboard area.%s', 'backup-backup');
  
  if (!$pros) {
    $bmiHidePromos = $basicMessageHidePromos . __('%sUpgrade to %sPremium%s today%s%s%sWe made it really affordable!%s', 'backup-backup');
    $bmiHidePromos = sprintf($bmiHidePromos, '<div class="bmi-center-text">', '<br>', '<a href="' . BMI_AUTHOR_URI . '" target="_blank">', '<span class="bmi-premium-bg-stars">', '</span>', '</a>', '<br>', '<b>', '</b>', '</div>');
  } else {
    $bmiHidePromos = $basicMessageHidePromos;
    $bmiHidePromos = sprintf($bmiHidePromos, '', '');
  }


  require_once BMI_INCLUDES . '/check/system_info.php';
  $is_curl_work = System_Info::is_curl_work();

?>

<div class="mt mb f18 lh30">
    
  <!--  -->
  <div class="mm">
    
    <div class="fo-title bold mbll">
      <?php _e('Email notifications', 'backup-backup'); ?> (#1)
    </div>
    
  </div>
  
  <div class="mm mbl">
    <div class="mm mm-border">
      
      <!--  -->
      <div class="cf">
        <div class="left mw250 lh65">
          <?php _e('Email address:', 'backup-backup'); ?>
        </div>
        <div class="left" style="max-width: calc(100% - 250px);">
          <div class="">
            <?php
              $ee = sanitize_text_field(bmi_get_config('OTHER:EMAIL'));
              if (strlen($ee) <= 1) {
                $ee = get_bloginfo('admin_email');
              }
            ?>
            <input type="text" id="email-for-notices" autocomplete="off" class="bmi-text-input small" value="<?php echo $ee; ?>" readonly onfocus="this.removeAttribute('readonly');" />
          </div>
          <div class="f16">
            <?php _e('This is where the log files will be sent to. You can enter several email addresses, separated by comma.', 'backup-backup'); ?>
          </div>
        </div>
      </div>
      
      <!--  -->
      <div class="cf mbl">
        <div class="left mw250 lh65">
          <?php _e('From field:', 'backup-backup'); ?>
        </div>
        <div class="left">
          <div class="">
            <input type="text" id="email-title-for-notices" autocomplete="off" class="bmi-text-input small" value="<?php echo sanitize_text_field(bmi_get_config('OTHER:EMAIL:TITLE')); ?>" />
          </div>
          <div class="f16">
            <?php _e('This will show up as the sender of the emails.', 'backup-backup'); ?>
          </div>
        </div>
      </div>

      <!--  -->
      <div class="cf mbl">

        <div class="left mw250 lh50" style="line-height: 145px;">
          <?php _e("You'll get an email if...", 'backup-backup'); ?>
        </div>

        <div class="left lh40">
          <table>
            <tbody>
              <tr>
                <td>
                  <label class="premium-<?php bmi_pro_features($pros, true, __('Receive an email notification when a backup is successfully created', 'backup-backup')); ?>">
                    <input type="checkbox" <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> id="backup-success-notify" <?php echo ($pros) ? bmi_try_checked('OTHER:BACKUP:SUCCEED:NOTIS') : '';  ?>>
                    <span <?php echo ($pros) ? "" : 'class="not-allowed"' ?>><?php _e("Backup was created successfully", 'backup-backup'); ?></span>
                    <span class="premium premium-img premium-ntt"></span>
                  </label>
                </td>
                <td>
                  <label class="premium-<?php bmi_pro_features($pros, true, __('Receive an email notification when a backup creation failed', 'backup-backup')); ?>">
                    <input type="checkbox" <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> id="backup-failed-notify" <?php echo ($pros) ? bmi_try_checked('OTHER:BACKUP:FAILED:NOTIS') : ''; ?>>
                    <span <?php echo ($pros) ? "" : 'class="not-allowed"' ?>><?php _e("Backup creation failed", 'backup-backup'); ?></span>
                    <span class="premium premium-img premium-ntt"></span>
                  </label>
                </td>
              </tr>
              <tr>
                <td>
                  <label class="premium-<?php bmi_pro_features($pros, true, __('Receive an email notification when a restore is successfully created', 'backup-backup')); ?>">
                    <input type="checkbox" <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> id="restore-success-notify" <?php echo ($pros) ? bmi_try_checked('OTHER:RESTORE:SUCCEED:NOTIS'): ''; ?>>
                    <span <?php echo ($pros) ? "" : ' class="not-allowed"' ?>><?php _e("Restore succeeded", 'backup-backup'); ?></span>
                    <span class="premium premium-img premium-ntt"></span>
                  </label>
                </td>
                <td>
                  <label class="premium-<?php bmi_pro_features($pros, true, __('Receive an email notification when a restore creation failed', 'backup-backup')); ?>">
                    <input <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> type="checkbox" id="restore-failed-notify" <?php echo ($pros) ? bmi_try_checked('OTHER:RESTORE:FAILED:NOTIS') : ''; ?>>
                    <span <?php echo ($pros) ? "" : ' class="not-allowed"' ?>><?php _e("Restore failed", 'backup-backup'); ?></span>
                    <span class="premium premium-img premium-ntt"></span>
                  </label>
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  <label for="scheduled-issues">
                    <input type="checkbox" id="scheduled-issues"<?php bmi_try_checked('OTHER:EMAIL:NOTIS'); ?>>
                    <span>
                      <?php _e("There are (new) issues with scheduling (automatic backup creation)", 'backup-backup'); ?><br>
                      <span class="f14">
                        <?php _e("(Make sure your hosting provider does not block mail functions. Otherwise, you will need to configure SMTP mail.)", 'backup-backup'); ?>
                      </span>
                    </span>
                  </label>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

      <!--  -->
      <div>
        <div class="cf radio-grid">

          <div class="left">
            <?php _e("Add logs to emails?", 'backup-backup'); ?>
          </div>

          <div class="left">
            <div class="left d-flex mr60 ia-center">
              <label class="container-radio <?php echo ($pros) ? "" : ' not-allowed' ?>">
                <?php _e("No", 'backup-backup'); ?>
                <input type="radio" name="add_logs_email" id="add-logs-email-no" <?php echo ($pros) ? "" : ' class="not-allowed" disabled checked' ?> <?php $pros ?  bmi_try_checked('OTHER:ATTACH:LOGS:TO:EMAIL', true): ''; ?>>
                <span class="checkmark-radio <?php echo ($pros) ? "" : ' not-allowed' ?>"></span>
              </label>
              <div class="inline cf premium-<?php bmi_pro_features($pros, true, __('The logs will be attached to the email in case of issues', 'backup-backup')); ?>">
                <label class="left container-radio ml25 <?php echo ($pros) ? "" : ' not-allowed' ?>">
                  <?php _e("Yes", 'backup-backup'); ?>
                  <input type="radio"  <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> name="add_logs_email" id="add-logs-email-yes" <?php $pros ? bmi_try_checked('OTHER:ATTACH:LOGS:TO:EMAIL'): ''; ?>>
                  <span class="checkmark-radio <?php echo ($pros) ? "" : ' not-allowed' ?>"></span>
                </label>
                <span class="left premium premium-img premium-nt mtf3"></span>
              </div>
            </div>
          </div>

        </div>

        <div class="cf radio-grid">

          <div class="left">
            <?php _e("Generate debug code?", 'backup-backup'); ?>
          </div>

          <div class="left">
            <div class="left d-flex mr60 ia-center">
              <label class="container-radio <?php echo ($pros) ? "" : ' not-allowed' ?>">
                <?php _e("No", 'backup-backup'); ?>
                <input type="radio"  name="generate_debug_code" id="generate-debug-code-no" <?php echo ($pros) ? "" : ' class="not-allowed" disabled checked' ?> <?php $pros ? bmi_try_checked('OTHER:ATTACH:DEBUG:CODE:TO:EMAIL',true): ''; ?>>
                <span class="checkmark-radio <?php echo ($pros) ? "" : ' not-allowed' ?>"></span>
              </label>
              <div class="inline cf premium-<?php bmi_pro_features($pros, true, __('The debug code will be attached to the email in case of issues', 'backup-backup')); ?>">
                <label class="left container-radio ml25 <?php echo ($pros) ? "" : ' not-allowed' ?>">
                  <?php _e("Yes", 'backup-backup'); ?>
                  <input type="radio" name="generate_debug_code" id="generate-debug-code-yes" <?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> <?php $pros ? bmi_try_checked('OTHER:ATTACH:DEBUG:CODE:TO:EMAIL'): ''; ?>>
                  <span class="checkmark-radio <?php echo ($pros) ? "" : ' not-allowed' ?>"></span>
                </label>
                <span class="left premium premium-img premium-nt mtf3"></span>
              </div>
            </div>
          </div>

        </div>

        <div class="f16 mtll">
          <?php _e("If you want to also receive the backup file as attachment of the email (for backup notifications), please set this in the chapter \"Where will backups be stored?\".", 'backup-backup'); ?>
        </div>
      </div>
    
    </div>
  </div>
  
  <hr>
  
  <!--  -->
  <div class="mm mtl mbll">
    
    <div class="fo-title bold">
      <?php _e("Backup triggers", 'backup-backup'); ?> (#2)
    </div>

    <div class="f16">
      <?php _e('At the top of this screen you can create a backup instantly (“Create backup now!” - button), or schedule backups. Here are additional options that can trigger backup creation:', 'backup-backup'); ?>
    </div>
    
  </div>

  <!--  -->
  <div class="mbl overlayed">
    
  <?php if (has_action('bmi_pro_backup_triggers')) : ?>
    <?php do_action('bmi_pro_backup_triggers'); ?>
  <?php else : ?>
    <?php include BMI_INCLUDES . '/dashboard/templates/premium-function-overlay.php'; ?>

    <!-- It is intended to use double .mm -->
    <div class="mm mbl">
      <div class="mm mm-border">
        <div>
          <div class="cf">
            <div class="left">
              <div class="f20 bold mr20 premium-wrapper">
                <?php _e("Before updates", 'backup-backup'); ?>
                <span class="premium premium-img premium-ntt"></span>
              </div>
            </div>
            <div class="left">
              <label for="before-updates-switch" class="bmi-switch">
                <input type="checkbox" disabled checked id="before-updates-switch">
                <div class="bmi-switch-slider round">
                  <span class="on"><?php _e("On", 'backup-backup'); ?></span>
                  <span class="off"><?php _e("Off", 'backup-backup'); ?></span>
                </div>
              </label>
            </div>
          </div>
        </div>
        
        <div>
          <div class="mtll f16">
            <?php _e("When enabled, this feature will create a <b>partial</b> backup before the automatic updates of WordPress core, plugins, or themes on your website. <b>Only</b> the item(s) about to get updated will be backed up.", 'backup-backup'); ?>
          </div>
        </div>
        
        <table>
          <tbody>
            <tr>
              <td style="vertical-align: top;">
                <div class="f20 bold mw250 lh65 premium-wrapper">
                  <?php _e("Trigger by URI", 'backup-backup'); ?>
                  <span class="premium premium-img premium-ntt"></span>
                </div>
              </td>
              <td>
                <div class="">
                  <div class="cf">
                    <div class="left mr20">
                      <input type="text" class="bmi-text-input small" id="trigger-input1" autocomplete="off"/>
                    </div>
                    <div class="left">
                      <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad left bmi-copper othersec mm30" data-copy="trigger-input1">
                        <div class="text">
                          <img src="<?php echo $this->get_asset('images', 'copy-icon.png'); ?>" alt="copy-img">
                          <div class="f18 semibold"><?php _e('Copy', 'backup-backup') ?></div>
                        </div>
                      </a>
                    </div>
                  </div>
                  <div class="f16 mtlll">
                    <?php _e("Copy and paste this URL into a browser and press Enter to trigger the backup creation.", 'backup-backup'); ?><br>
                    <?php _e("Make sure to keep this URL secret. For safety reasons, this trigger works only once per hour, and you will be emailed when it is used.", 'backup-backup'); ?>
                  </div>
                  <div class="mtll cf">
                    <div class="left lh60 mr20"><?php _e("Key:", 'backup-backup'); ?></div>
                    <div class="left mr20">
                      <input type="text" class="bmi-text-input small" autocomplete="off"/>
                    </div>
                    <div class="left">
                      <a href="#" class="btn mm30 othersec"><?php _e("Save", 'backup-backup'); ?></a>
                    </div>
                  </div>
                  <div class="f16 mtlll">
                    <?php _e("Change the Key (which is part of the URL above) if you suspect that an unauthorized person got access to it.", 'backup-backup'); ?>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>
  </div>
  
  <!--  -->
  <div class="mbl mtl overlayed" style="display: none;">

    <?php include BMI_INCLUDES . '/dashboard/templates/premium-overlay.php'; ?>

    <div class="mm fo-title bold premium-wrapper">
      <?php _e("WP CLI", 'backup-backup'); ?>
      <span class="premium premium-img premium-ntt"></span>
    </div>
    <div class="mm mtll f16">
      <?php _e('Trigger backups via WP CLI.', 'backup-backup'); ?>
    </div>
    <div class="mm mtll">
      <?php _e('If you selected the "schedule backups" - option at the top of the plugin, and backups are not created, then please check out the Cron settings. Or just  ask us in the forum.', 'backup-backup'); ?>
    </div>
  </div>
  
  <hr>

  <!--  -->
  <div class="mm mbl mtl">
    <div class="fo-title mbll bold">
      <?php _e("Change functionality of the plugin", 'backup-backup'); ?> (#3)
    </div>
    <div class="mm mm-border">
      <div>
        <span class="relative">
          <?php _e("Some", 'backup-backup'); ?> <b><?php _e("experimental", 'backup-backup'); ?></b> <?php _e("features", 'backup-backup'); ?>:
          &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info; ?>"></span>
        </span>
      </div>

      <div class="lh40">
        <label for="normal-timeout" class="container-radio">
          <input type="radio" name="experimental_features" id="normal-timeout"<?php bmi_try_checked('OTHER:USE:TIMEOUT:NORMAL'); ?> />
          <span class="f18">
            <?php _e("Method 1 - Do not change the default plugin functions - it may require adjusting your server for stable backup.", 'backup-backup'); ?>
            &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_1; ?>"></span>
          </span>
          <span class="checkmark-radio" style="margin-top: 2px;"></span>
        </label>
      </div>
      <div class="lh40 <?php echo ($is_curl_work) ? "": "container-radio-disabled"; ?>">
        <label for="experimental-timeout" class="container-radio">
          <input type="radio" name="experimental_features" id="experimental-timeout"<?php bmi_try_checked('OTHER:EXPERIMENT:TIMEOUT'); ?> <?php echo ($is_curl_work) ? "" : 'disabled'; ?> />
          <span class="f18">
            <?php _e("Method 2 - Bypass the web server timeout directive - the backup process may be slower.", 'backup-backup'); ?>
            &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_2; ?>"></span>
          </span>
          <span class="checkmark-radio" style="margin-top: 2px;"></span>
        </label>
        <?php if (!$is_curl_work): ?>
          <div class="error-msg">
            <span><?php _e("This feature requires cURL PHP extension installed to be used", 'backup-backup'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="lh40">
        <label for="experimental-hard-timeout" class="container-radio">
          <input type="radio" name="experimental_features" id="experimental-hard-timeout"<?php bmi_try_checked('OTHER:EXPERIMENT:TIMEOUT:HARD'); ?> />
          <span class="f18">
            <?php _e("Method 3 - Bypass the web server limits - it will disable automatic backup and the possibility of running it in the background.", 'backup-backup'); ?>
            &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_3; ?>"></span>
          </span>
          <span class="checkmark-radio" style="margin-top: 2px;"></span>
        </label>
      </div>
    </div>
  </div>
  
  <hr>

  <!--  -->
  <div class="mbl mtl">
    <div class="mm fo-title mbll bold">
      <?php _e("Backup & Restore – PHP CLI Settings (advanced)", 'backup-backup'); ?> (#4)
    </div>

    <div class="mm">
      <div class="mm mm-border">
        <div class="cf">
          <div class="left mw250 lh65">
            <?php _e('PHP CLI executable path:', 'backup-backup'); ?>
          </div>
          <div class="left">
            <div class="">
              <?php $cli_path = sanitize_text_field(bmi_get_config('OTHER:CLI:PATH')); ?>
              <input type="text" id="cli-manual-path" class="bmi-text-input small" value="<?php echo $cli_path; ?>" placeholder="<?php _e("Automatic", 'backup-backup'); ?>" autocomplete="off" />
            </div>
            <div class="f16">
              <?php _e('This field has no effect if PHP CLI is not available on the server or it is disabled due to settings.', 'backup-backup'); ?><br>
              <?php _e('Please leave it empty if you do not know what you are doing, unless our support has told you what it does.', 'backup-backup'); ?>
            </div>
          </div>
        </div>

        <div class="lh40">
          <label for="cli-disable-others">
            <input type="checkbox" id="cli-disable-others"<?php bmi_try_checked('OTHER:CLI:DISABLE'); ?> />
            <span><?php _e("Disable PHP CLI checking; try using alternate methods.", 'backup-backup'); ?></span>
          </label>
        </div>
      </div>
    </div>
  </div>
  
  <hr>
  
  <!--  -->
  <div class="mm mbl mtl">
    <div class="fo-title mbll bold">
      <?php _e("Change basic functions of the plugin", 'backup-backup'); ?> (#5)
    </div>
    <div class="mm mm-border">
      
      <div>
        <span class="relative">
          <?php printf(__("Backup %sdownloading%s technique", 'backup-backup'), '<b>', '</b>'); ?>:
        </span>
      </div>
      <div class="lh40">
        <label for="download-technique">
          <input type="checkbox" id="download-technique"<?php bmi_try_checked('OTHER:DOWNLOAD:DIRECT'); ?> />
          <span><?php _e("Use direct downloading - it will remove .htaccess protection right before the download (it can solve download issues).", 'backup-backup'); ?></span>
        </label>
      </div>
      
      <div class="mtll">
        <span class="relative">
          <?php _e("Additional actions during", 'backup-backup'); ?> <b><?php _e("restoration", 'backup-backup'); ?></b>:
        </span>
      </div>
      <div class="lh40">
        <label for="remove-assets-before-restore">
          <input type="checkbox" id="remove-assets-before-restore"<?php bmi_try_checked('OTHER:RESTORE:BEFORE:CLEANUP'); ?> />
          <span class="relative"><?php _e("Remove existing plugins and themes before migration.", 'backup-backup'); ?><span class="bmi-info-icon tooltip" tooltip="<?php echo $cleanupbeforerestore; ?>"></span></span>
        </label>
      </div>      
    </div>
  </div>
  
  <hr>

  <!--  -->
  <div class="mm mbl mtl">

    <div class="fo-title bold">
      <?php _e("Database import/export settings", 'backup-backup'); ?> (#6)
    </div>
    
    <div class="mbll">
      <span class="relative">
        <?php _e("Adjust the amount of queries per batch for your ", 'backup-backup'); ?> <b><?php _e("database", 'backup-backup'); ?></b>
        <span class="bmi-info-icon tooltip" tooltip="<?php echo $db_query_info; ?>"></span>
      </span><br>
    </div>
    
    <div class="mm mm-border">
    
      <div class="lh40 cf">
        <div class="left mw250 lh65">
          <?php _e("Queries per batch for import/export: ", 'backup-backup'); ?>&nbsp;
        </div>
        <div class="left">
          <?php $query_amount = sanitize_text_field(bmi_get_config('OTHER:DB:QUERIES')); ?>
          <label for="db_queries_amount">
            <input type="number" id="db_queries_amount" class="bmi-text-input small" value="<?php echo $query_amount; ?>" placeholder="2000" min="15" max="15000" />
          </label>
        </div>
      </div>

      <div class="lh40 cf">
        <div class="left mw250 lh65">
          <?php _e("Search & Replace max Page Size: ", 'backup-backup'); ?>&nbsp;
        </div>
        <div class="left">
          <?php $sr_max_amount = sanitize_text_field(bmi_get_config('OTHER:DB:SEARCHREPLACE:MAX')); ?>
          <label for="db_search_replace_max">
            <input type="number" id="db_search_replace_max" class="bmi-text-input small" value="<?php echo $sr_max_amount; ?>" placeholder="300" min="10" max="30000" />
          </label>
        </div>
      </div>

      <div class="lh40 cf">
        <div class="left mw250 lh65">
          <?php _e("File limit for extraction batching (set to \"auto\" for automatic choice): ", 'backup-backup'); ?>&nbsp;
        </div>
        <div class="left">
          <?php $fel_max_amount = sanitize_text_field(bmi_get_config('OTHER:FILE:EXTRACT:MAX')); ?>
          <label for="file_limit_extraction_max">
            <input type="text"  autocomplete="off" id="file_limit_extraction_max" class="bmi-text-input small" value="<?php echo $fel_max_amount; ?>" placeholder="auto" min="50" max="20000" />
          </label>
        </div>
      </div>

      <div class="lh40">
        <label for="bmi-restore-splitting">
          <input type="checkbox" id="bmi-restore-splitting"<?php bmi_try_checked('OTHER:RESTORE:SPLITTING'); ?> />
          <span class="relative"><?php _e("Enable SQL splitting for the migration process.", 'backup-backup'); ?> <span class="bmi-info-icon tooltip" tooltip="<?php echo $sqlsplitting; ?>"></span></span>
        </label>
      </div>

      <div class="lh40">
        <label for="bmi-db-v3-restore-engine">
          <input type="checkbox" id="bmi-db-v3-restore-engine"<?php bmi_try_checked('OTHER:RESTORE:DB:V3'); ?> />
          <span><?php _e("Restoration: Perform new Search & Replace after database import.", 'backup-backup'); ?></span>
        </label>
      </div>

      <div class="lh40">
        <label for="bmi-db-batching-backup">
          <input type="checkbox" id="bmi-db-batching-backup"<?php bmi_try_checked('OTHER:BACKUP:DB:BATCHING'); ?> />
          <span class="relative">
            <?php _e("Use batching technique for database export (backup).", 'backup-backup'); ?>
            <span class="bmi-info-icon tooltip" tooltip="<?php echo $dbbatching; ?>"></span>
          </span>
        </label>
      </div>

      <div class="lh40">
        <label for="bmi-db-single-file-backup">
          <input type="checkbox" id="bmi-db-single-file-backup"<?php bmi_try_checked('OTHER:BACKUP:DB:SINGLE:FILE'); ?> />
          <span class="relative">
            <?php _e("Deprecated: Force the plugin to backup all tables into one file.", 'backup-backup'); ?>
            <span class="bmi-info-icon tooltip" tooltip="<?php echo $deprecatedsinglefile; ?>"></span>
          </span>
        </label>
      </div>
      
    </div>

  </div>
  
  <hr>

  <!--  -->
  <div class="mm mbl mtl">
    
    <div class="fo-title mbll bold">
      <?php _e("Trust settings", 'backup-backup'); ?> (#7)
    </div>
    
    <div class="mm mm-border">
      <div class="lh40">
        <label for="bmi-do-not-check-free-space-backup">
          <input type="checkbox" id="bmi-do-not-check-free-space-backup"<?php bmi_try_checked('OTHER:BACKUP:SPACE:CHECKING'); ?> />
          <span class="relative">
            <?php _e("Disable space checking during backup and restore process - please read additional info.", 'backup-backup'); ?>
            <span class="bmi-info-icon tooltip" tooltip="<?php echo $disabledspacechecking; ?>"></span>
          </span>
        </label>
      </div>
    </div>
    
  </div>
  
  <hr>

  <!--  -->
  <div class="mm mbl mtl">
    
    <div class="fo-title bold">
      <?php _e("Clean-ups", 'backup-backup'); ?> (#8)
    </div>
    
    <div class="mbll">
      <span class="relative">
        <?php _e("When this plugin is", 'backup-backup'); ?> <b><?php _e("uninstalled:", 'backup-backup'); ?></b>
        &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $deinstalled_info; ?>"></span>
      </span><br>
    </div>

    <div class="mm mm-border">
      <div class="lh40">
        <label for="uninstalling-configs">
          <input type="checkbox" id="uninstalling-configs"<?php bmi_try_checked('OTHER:UNINSTALL:CONFIGS'); ?> />
          <span><?php _e("Delete all plugin settings (this means that if you install it again, you will have to configure it again).", 'backup-backup'); ?></span>
        </label>
      </div>
      <div class="lh40">
        <label for="uninstalling-backups">
          <input type="checkbox" id="uninstalling-backups"<?php bmi_try_checked('OTHER:UNINSTALL:BACKUPS'); ?> />
          <span><?php _e("Delete all backups (created by this plugin)", 'backup-backup'); ?></span>
        </label>
      </div>
    </div>
    
  </div>

  <hr>

  <div class="mm mbl mtl">
    <div class="fo-title bold">
      <?php _e("Other Premium Options", 'backup-backup'); ?> (#9)
    </div>
  
    <div class="mbll">
      <span class="relative">
        <?php _e("Display settings", 'backup-backup'); ?>:
      </span>
    </div>
    <div class="mm mm-border">
      <div class="lh40">
        <label for="hide-promotional-bmi-banners">
          <input type="checkbox"<?php echo ($pros) ? "" : ' class="not-allowed" disabled' ?> id="hide-promotional-bmi-banners"<?php bmi_try_checked('OTHER:PROMOTIONAL:DISPLAY'); ?> />
          <span class="relative<?php echo ($pros) ? "" : ' not-allowed' ?>"><?php _e("Hide promotional banners and carousel.", 'backup-backup'); ?>
            <span class="inline premium-wrapper<?php echo (!$pros) ? "" : ' is-pro' ?>" tooltip="<?php echo esc_attr( $bmiHidePromos ); ?>">
              <span class="premium premium-img premium-ntt"></span>
            </span>
          </span>
        </label>
      </div>
    </div>
  </div>

  <hr>

  <!--  -->
  <div class="mm mtll">
    <?php _e("If you're looking for other options not listed above, check out the", 'backup-backup'); ?> <a href="#" class="secondary hoverable nodec collapser-openner" data-el="#troubleshooting-chapter"><?php _e("troubleshooting", 'backup-backup'); ?></a> <?php _e("chapter as they might be there.", 'backup-backup'); ?>
  </div>

</div>

<?php include BMI_INCLUDES . '/dashboard/chapter/save-button.php'; ?>
