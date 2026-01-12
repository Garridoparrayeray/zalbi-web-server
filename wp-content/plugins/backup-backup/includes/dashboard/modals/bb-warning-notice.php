<?php

// Disallow direct access
if (!defined('ABSPATH')) exit;


$preorder = 'https://backupbliss.com/pricing';

?>
<div <?php echo isset($global_warning) ? 'id="bb-warning-notice"' : ''; ?>>
<div class="bb-upload-fail-warning space-between flexcenter" <?php echo isset($global_warning) ? 'style="margin-bottom: 15px"' : ''; ?>>
    <div class="warning-text space-between flexcenter">
        <img src="<?php echo $this->get_asset('images', 'warning-white.png') ?>" alt="warning" class="warning-img">
        <span class="f20">
            <?php _e($error_message, 'backup-backup'); ?>
        </span>
    </div>
    <div class="get-more-storage">
        <a href="<?php echo BMI_AUTHOR_URI . 'pricing' ?>" target="_blank"
            class="f16 bold nodec black"><?php _e("Get more storage now", 'backup-backup'); ?></a>
    </div>
</div>
<?php if (isset($global_warning)): ?>
<div class="bb-credit-text">By Backup & Migration [<a href="#" style="text-decoration: none;" id="bb-upload-fail-dismiss">X</a>]</div>
<?php endif ?>
</div>