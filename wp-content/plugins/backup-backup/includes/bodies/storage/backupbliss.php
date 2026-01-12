<!-- BackupBliss Storage -->
<div class="tab2-item d-flex jst-sb ia-center backupbliss-storage">
    <div class="d-flex ia-center">
        <img src="<?php echo $this->get_asset('images', 'logo-white.svg') ?>" alt="logo" class="tab2-img">
        <span class="ml25">
        <span class="title_whereStored"><?php _e("BackupBliss", 'backup-backup'); ?></span>
        </span>
    </div>
    <div class="ia-center">
    </div>
</div>

<div class="bg_grey" id="storage-backupbliss-row">
<div class="container-40 lh30 pt30 pb30">
    <p class="f18 bb-storage-info">
    <?php _e("Keep a copy of your backups in an external storage facility so that <b>you’re safe no matter what happens to your site or domain.</b>", 'backup-backup'); ?>
    </p>
    <!-- How it work section -->
    <!-- When connected add style="display: none;" -->
    <div class="how-it-works" <?php echo(get_option("bmi_pro_backupbliss_key", false) !== false ? "style='display: none'" : "") ?>> 
    <span class="bold f22"><?php _e("How it works (it's easy!):", 'backup-backup'); ?></span>
    <ol class="setup-steps">
        <li class="step sign-up">
        <div class="counter">
            <img src="<?php echo $this->get_asset('images', 'right-bolt.svg') ?>" class="counter-arrow">
        </div>
        <div class="step-content space-between flexcenter title">
            <span
            class="f20 medium block"><?php _e("Sign Up on BackupBliss to <b>claim your 1 GB of free storage</b>", 'backup-backup'); ?></span>
            <a href="<?php echo BMI_BB_STORAGE_URI; ?>" target="_blank" class="btn bold"><?php _e("Sign up now", 'backup-backup'); ?></a>
        </div>
        </li>
        <hr class="step-divider">
        <li class="step get-more-space">

        <div class="step-content">
            <div class="counter">
            <img src="<?php echo $this->get_asset('images', 'right-bolt.svg') ?>" class="counter-arrow">
            </div>
            <div class="title">
            <span class="f20 medium block"><?php _e("Get More Space", 'backup-backup'); ?></span>
            </div>
            <div class="description">
            <span class="f18 mbll">
                <?php
                echo sprintf(
                __("If the free 1 GB aren’t enough, you can get more space. It’s very affordable (see %spricing%s).<br>You can buy space directly in your %sBackupBliss account%s.", 'backup-backup'),
                '<a href="' . BMI_AUTHOR_URI . 'pricing' . '" target="_blank" class="secondary hoverable nodec">',
                '</a>',
                '<a href="' . BMI_BB_STORAGE_URI . '" target="_blank" class="secondary hoverable nodec">',
                '</a>'
                );
                ?>
            </span>
            <span class="f15 block tml"><?php
            echo sprintf(
                __("<b>Note:</b> The %sBackupBliss premium plugin%s gives you <b>5 GB of free storage!</b>", 'backup-backup'),
                '<a href="' . BMI_AUTHOR_URI . '" target="_blank" class="secondary hoverable nodec">',
                '</a>'
            );
            ?>
            </span>
            </div>
        </div>
        </li>
        <hr class="step-divider">
        <li class="step connect">

        <div class="step-content">
            <div class="counter">
            <img src="<?php echo $this->get_asset('images', 'right-bolt.svg') ?>" class="counter-arrow">
            </div>
            <div class="title">
            <span class="f20 medium block"><?php _e("Connect this site to your account", 'backup-backup'); ?></span>
            </div>
            <div class="description space-between flexcenter" style="gap: 12px;">
            <div class="f18" style="margin-right: 30px;">
                <span class="f18"><?php _e("Enter the API key you<br>generated on", 'backup-backup'); ?>
                <a href="<?php echo BMI_BB_STORAGE_URI; ?>" target="_blank" class="secondary hoverable nodec"><?php _e("BackupBliss:", 'backup-backup'); ?></a>
                </span>
            </div>
            <input type="text" placeholder="<?php _e("E.g. 2122bf590c5c9f5778bae6ac38b1d121455fa57e5f9b2ef7c37b7bdd334fa925", 'backup-backup'); ?>"
                class="api-key-input" style="flex: 1;" autocomplete="off">
            <a class="btn bold bb-connect"><?php _e("Connect now", 'backup-backup'); ?></a>
            </div>
        </div>
        </li>
    </ol>
    <div class="f15">
        <?php echo sprintf(
        __("Have questions? %sCheck out the FAQ%s", 'backup-backup'),
        '<a href="' . BMI_BB_STORAGE_URI . 'faq' . '" target="_blank" class="secondary hoverable nodec">',
        '</a>'
        ); ?>
    </div>
    </div>
    <!-- You're connected section -->
    <!-- When connected remove style="display: none;" -->
    <div class="youre-connected" <?php echo(get_option("bmi_pro_backupbliss_key", false) === false ? "style='display: none'" : "") ?>>
    <div class="connected-banner space-between flexcenter">
        <div class="connected-banner-inner space-between flexcenter">
        <img src="<?php echo $this->get_asset('images', 'checkmark.svg') ?>" alt="checkmark" class="checkmark">
        <span class="f30 bold mms"><?php _e("You’re connected!", 'backup-backup'); ?></span>
        </div>
        <a href="#" class="bb-disconnect f15 nodec"><?php _e("Disconnect", 'backup-backup'); ?></a>
    </div>

    <div class="connected-info space-between flexcenter">
        <div class="connected-info-inner">
        <span class="f18"><?php _e("You are currently entitled to", 'backup-backup'); ?></span>
        <!-- Placeholder -->
        <span class="f30 bold bb-storage-amount"><?php _e("0.0 GB", 'backup-backup'); ?></span>
        <span class="f18"><?php _e("of space of which you are using", 'backup-backup'); ?></span>
        <!-- Placeholder -->
        <span class="f30 bold bb-storage-used"><?php _e("0.0 GB", 'backup-backup'); ?></span> 
        <!-- Placeholder -->
        <span class="f30 bb-storage-used-percent">(0%)</span>
        </div>
        <div class="refresh-bb-storage pointer">
        <img src="<?php echo $this->get_asset('images', 'refresh.svg') ?>" alt="refresh" class="refresh-img"> 
        <a class="secondary hoverable nodec"><?php _e("Refresh", 'backup-backup'); ?></a>
        </div>
    </div>       
    <?php
        use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;
        if (file_exists(BMI_INCLUDES . '/external/backupbliss.php')) {
            require_once BMI_INCLUDES . '/external/backupbliss.php';
            $backupbliss = new BackupBliss();
            $upload_issue_notice = $backupbliss->getNotice("upload_issue_space");
            if ($upload_issue_notice) {
                $error_message = $upload_issue_notice;
                include BMI_INCLUDES . '/dashboard/modals/bb-warning-notice.php';
            }
        } 
    ?>
    <div>
        <span class="f15">
        <?php echo sprintf(
            __("Storage space is %svery affordable%s. <b>Note:</b> %sThe BackupBliss premium plugin%s already gives you 5 GB of free space!", 'backup-backup'),
            '<a href="' . BMI_AUTHOR_URI . 'pricing' . '" target="_blank" class="secondary hoverable nodec">',
            '</a>',
            '<a href="' . BMI_AUTHOR_URI . '" target="_blank" class="secondary hoverable nodec">',
            '</a>'
        ); ?>
    </div>
    </div>
</div>
</div>