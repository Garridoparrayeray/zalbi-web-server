<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div id="delete-prompt" class="custom-prompt">
    <div class="custom-prompt__box">
        <span class="custom-prompt__icon">
            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/delete-icon-trash.svg"); ?>" alt="<?php _e( "trash icon", "redirect-redirection" ); ?>">
        </span>
        <p class="custom-prompt__message-heading">
            <?php _e( "Delete redirect?", "redirect-redirection" ); ?>
        </p>
        <button class="custom-prompt__close-btn ir-delete-confirmation-close ir-delete-confirmed" style="background-color: #da2b2b;"><?php _e( "Yes, delete", "redirect-redirection" ); ?></button>
        <button class="custom-prompt__discard-btn ir-delete-confirmation-close"><?php _e( "Cancel", "redirect-redirection" ); ?></button>
    </div>
</div>