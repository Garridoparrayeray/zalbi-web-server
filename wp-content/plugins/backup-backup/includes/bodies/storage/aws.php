<?php

// Namespace
namespace BMI\Plugin\Dashboard;

// use S3
use BMI\Plugin\External\BMI_External_S3 as S3;
// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('bmi_pro_aws_s3_template', function () {

    require_once BMI_INCLUDES . '/external/s3.php';
    //HERE
    $s3 = new S3('aws');
    $configs = $s3->retrieveS3Configs();
    $accessKey = $configs['accessKey'];
    $secretKey = $configs['secretKey'];
    $bucket = $configs['bucket'];
    $storageClass = $configs['storageClass'];
    $region = $configs['region'];
    $sse = $configs['sse'];
    $path = $configs['path'];
    $shouldBeConnected = false;
    $s3_regions = $s3->getRegions();
    $isEnabled = bmi_get_config('STORAGE::EXTERNAL::AWS');
    if ($isEnabled === true || $isEnabled === 'true') {
        $isEnabled = ' checked';
    } else {
        $isEnabled = '';
    }

    ?>
    <!-- External: S3 -->
    <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">

        <div class="d-flex ia-center">
            <img src="<?php echo $this->get_asset('images', 'Amazon.svg') ?>" alt="logo" class="tab2-img">
            <span class="ml25 title_whereStored">Amazon S3</span>
        </div>

        <div class="ia-center">
            <div class="b2 bmi-switch">
                <input type="checkbox" class="checkbox storage-checkbox" <?php echo $isEnabled; ?>
                    data-toggle="storage-s3-row" id="bmi-pro-storage-aws-toggle">
                <div class="bmi-knobs"><span></span></div>
                <div class="bmi-layer_str"></div>
            </div>
        </div>

    </div>

    <div class="bg_grey storage_target" id="storage-s3-row" <?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <div class="container-40 lh30 pt30">

            <!-- Backup Directory Path -->
            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Backup Directory Path:</span></div>
                <div class="w100 pos-r">
                    <div class="w100 pos-r">
                        <input id="bmip-aws-path" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field($path); ?>"
                            <?php echo ($shouldBeConnected) ? ' readonly ' : ''; ?>
                            placeholder="backups/wordpress">
                    </div>
                </div>
            </div>

            <!--  Access Key -->
            <?php if (! $shouldBeConnected) { ?>
                <div class="d-flex aws-access-key-container">
                    <div class="w270" style="margin-top: 23px;"><span>Access Key:</span></div>
                    <div style="width: 305px;">
                        <div class="w100 pos-r">
                            <input id="bmip-aws-access-key" class="input-ftpdrive_storage" type="text" autocomplete="off"
                                placeholder="Your S3 Access Key" required>
                        </div>
                    </div>
                </div>

                <!-- Secret Key -->
                <div class="d-flex aws-secret-key-container">
                    <div class="w270" style="margin-top: 23px;"><span>Secret Key:</span></div>
                    <div style="width: 305px;">
                        <div class="w100 pos-r">
                            <input id="bmip-aws-secret-key" class="input-ftpdrive_storage" type="password" autocomplete="off"
                                placeholder="Your S3 Secret Key" required>
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <!-- Bucket Name -->
            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Bucket Name:</span></div>
                <div style="width: 305px;">
                    <div class="pos-r">
                        <input id="bmip-aws-bucket" class="input-ftpdrive_storage" type="text" autocomplete="off" <?php echo ($shouldBeConnected) ? 'readonly' : ''; ?>
                            value="<?php echo sanitize_text_field($bucket); ?>"
                            placeholder="my-backup-bucket" required>
                    </div>
                </div>
            </div>

            <!-- Region -->
             <?php if (! $shouldBeConnected) { ?>
                <div class="d-flex region-container" style="margin-top: 8px;margin-bottom: 17px;">
                    <div class="w270" style="margin-top: 8px;"><span>Region:</span>
                    </div>
                    <div class="info-dropdown-container">
                        <select id="bmip-aws-region" class="input-ftpdrive_storage" required data-def="<?php echo sanitize_text_field($region); ?>" data-width="255" data-classes="pt-5 pb-5" data-readonly="<?php echo ($shouldBeConnected) ? 'true' : 'false'; ?>">
                            <?php
                            foreach ($s3_regions as $key => $value) {
                                $selected = ($key === $region) ? 'selected' : '';
                                echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <!-- Storage Class -->
            <div class="d-flex" style="margin-top: 8px;">
                <div class="w270" style="margin-top:8px"><span>Storage Class:</span></div>
                <div class="info-dropdown-container flex-here baseline" style="gap: 5px">
                    <div class="w100 pos-r">
                        <select id="bmip-aws-storage-class" class="input-ftpdrive_storage" required data-def="<?php echo sanitize_text_field($storageClass); ?>" data-width="255" data-classes="pt-5 pb-5" data-readonly="<?php echo ($shouldBeConnected) ? 'true' : 'false'; ?>">
                            <option value="STANDARD">Standard</option>
                            <option value="STANDARD_IA">Standard-IA</option>
                            <option value="REDUCED_REDUNDANCY">Reduced Redundancy</option>
                        </select>
                    </div>
                    <span class="bmi-info-icon tooltip" tooltip="<?php _e('Storage classes are different levels of redundancy and availability for your data. Standard is the default class, while Standard-IA and Reduced Redundancy are lower-cost options with different availability and durability characteristics.', 'backup-backup'); ?>"></span>

                </div>
            </div>

            <!-- Server-side Encryption -->
            <div class="d-flex mb8 mt8 baseline" style="margin-top: 16px;">
                <div class="w270" style="margin-top:8px"><span>Server-side Encryption:</span></div>
                <div class="info-dropdown-container flex-here baseline" style="gap: 8px">
                    <div class="w100 pos-r">
                        <label class="checkbox-container" <?php echo ($shouldBeConnected) ? 'style="cursor: not-allowed;"' : ''; ?>>
                            <input type="checkbox" id="bmip-aws-sse" class="input-ftpdrive_storage" value="AES256" <?php echo ( $sse === 'AES256') ? 'checked' : ''; ?> <?php echo ($shouldBeConnected) ? 'disabled style="cursor: not-allowed;"' : ''; ?>>
                            <span class="checkmark"></span>
                            <span class="checkbox-label">Enable AES-256 encryption</span>
                        </label>
                    </div>
                    <span class="bmi-info-icon tooltip" tooltip="<?php _e('Server-side encryption (SSE) is a feature that automatically encrypts data before it is written to disk. This feature helps protect your data at rest.', 'backup-backup'); ?>"></span>
                </div>
            </div>

        </div>

        <div id="aws-unauthenticated-box" class="container-40 lh30 pt30 pb30" <?php echo (($shouldBeConnected) ? 'style="display: none;"' : ''); ?>>
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;">
                    <span id="s3drive-not-authed-content-box" class="external-storage-not-authed-content">
                        Current status:&nbsp;<b>Inactive</b>
                    </span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="aws-connect-btn" class="btn external-storage-btn-connection"><?php _e("Connect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <blockquote class="bmi-ftpdrive-info">
                    Amazon S3 external storage provides a secure, durable, and scalable solution for storing your backup files. 
                    With various storage classes and regions available, you can optimize for both cost and performance while 
                    ensuring your backups are safely stored in the cloud.
                </blockquote>
            </div>
        </div>

        <div id="aws-authenticated-box" class="container-40 lh30 pt30 pb30" <?php echo (($shouldBeConnected) ? '' : 'style="display: none;"'); ?>>
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;">
                    <span id="s3drive-authed-content-box" class="external-storage-authed-content">
                        Current status:&nbsp;<b>Active</b>
                    </span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="aws-disconnect-btn" class="btn external-storage-btn-connection"><?php _e("Disconnect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
});