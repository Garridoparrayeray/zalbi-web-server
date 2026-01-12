<?php

// Namespace
namespace BMI\Plugin\Dashboard;

// use S3
use BMI\Plugin\External\BMI_External_S3 as S3;
// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('bmi_pro_wasabi_template', function () {

    require_once BMI_INCLUDES . '/external/s3.php';
    
    $s3 = new S3('wasabi');
    $configs = $s3->retrieveS3Configs();
    $accessKey = $configs['accessKey'];
    $secretKey = $configs['secretKey'];
    $bucket = $configs['bucket'];
    $region = $configs['region'];
    $path = $configs['path'];
    $shouldBeConnected = false;
    $wasabi_regions = $s3->getRegions();

    
    $isEnabled = bmi_get_config('STORAGE::EXTERNAL::WASABI');
    if ($isEnabled === true || $isEnabled === 'true') {
        $isEnabled = ' checked';
    } else {
        $isEnabled = '';
    }

    ?>
    <!-- External: Wasabi -->
    <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">

        <div class="d-flex ia-center">
            <img src="<?php echo $this->get_asset('images', 'wasabi.svg')?>" alt="logo" class="tab2-img">
            <span class="ml25 title_whereStored">Wasabi</span>
        </div>

        <div class="ia-center">
            <div class="b2 bmi-switch">
                <input type="checkbox" class="checkbox storage-checkbox" <?php echo $isEnabled; ?>
                    data-toggle="storage-wasabi-row" id="bmi-pro-storage-wasabi-toggle">
                <div class="bmi-knobs"><span></span></div>
                <div class="bmi-layer_str"></div>
            </div>
        </div>

    </div>

    <div class="bg_grey storage_target" id="storage-wasabi-row" <?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <div class="container-40 lh30 pt30">

            <!-- Backup Directory Path -->
            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Backup Directory Path:</span></div>
                <div class="w100 pos-r">
                    <div class="w100 pos-r">
                        <input id="bmip-wasabi-path" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field($path); ?>"
                            placeholder="backups/wordpress">
                    </div>
                </div>
            </div>

            <!-- Bucket Name -->
            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Bucket Name:</span></div>
                <div style="width: 305px;">
                    <div class="pos-r">
                        <input id="bmip-wasabi-bucket" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field($bucket); ?>"
                            placeholder="my-backup-bucket" required>
                    </div>
                </div>
            </div>
            
            
            <!-- Region -->
            <div class="d-flex region-container" style="margin-top: 8px;margin-bottom: 6px;">
                <div class="w270" style="margin-top: 12px;"><span>Region:</span>
                </div>
                <div class="info-dropdown-container">
                    <select id="bmip-wasabi-region" class="input-ftpdrive_storage" required data-def="<?php echo sanitize_text_field($region); ?>" data-width="255" data-classes="pt-5 pb-5" data-readonly="<?php echo ($shouldBeConnected) ? 'true' : 'false'; ?>">
                        <?php
                        foreach ($wasabi_regions as $key => $value) {
                            $selected = ($key === $region) ? 'selected' : '';
                            echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!--  Access Key -->
            <div class="d-flex wasabi-access-key-container">
                <div class="w270" style="margin-top: 23px;"><span>Access Key:</span></div>
                <div style="width: 305px;">
                    <div class="w100 pos-r">
                        <input id="bmip-wasabi-access-key" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            placeholder="Your Wasabi Access Key" required>
                    </div>
                </div>
            </div>

            <!-- Secret Key -->
            <div class="d-flex wasabi-secret-key-container">
                <div class="w270" style="margin-top: 23px;"><span>Secret Key:</span></div>
                <div style="width: 305px;">
                    <div class="w100 pos-r">
                        <input id="bmip-wasabi-secret-key" class="input-ftpdrive_storage" type="password" autocomplete="off"
                            placeholder="Your Wasabi Secret Key" required>
                    </div>
                </div>
            </div>




        </div>

        <div id="wasabi-unauthenticated-box" class="container-40 lh30 pt30 pb30">
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;">
                    <span id="wasabi-not-authed-content-box" class="external-storage-not-authed-content">
                        Current status:&nbsp;<b>Inactive</b>
                    </span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="wasabi-connect-btn" class="btn external-storage-btn-connection"><?php _e("Connect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <blockquote class="bmi-ftpdrive-info">
                    Wasabi external storage provides a secure, high-performance, and cost-effective solution for storing your backup files. 
                    With no egress fees or API request charges, Wasabi offers a predictable pricing model with performance that meets or 
                    exceeds comparable S3 storage services.
                </blockquote>
            </div>
        </div>

        <div id="wasabi-authenticated-box" class="container-40 lh30 pt30 pb30">
            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;">
                    <span id="wasabi-authed-content-box" class="external-storage-authed-content">
                        Current status:&nbsp;<b>Active</b>
                    </span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="wasabi-disconnect-btn" class="btn external-storage-btn-connection"><?php _e("Disconnect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
});