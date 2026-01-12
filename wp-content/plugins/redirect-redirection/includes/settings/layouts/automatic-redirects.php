<?php
if (!defined("ABSPATH")) {
    exit();
}

$isLogMeWhereIFinishedEnabled = $this->dbManager->isLogMeWhereIFinishedEnabled();
$LMWIFStatus = checked($isLogMeWhereIFinishedEnabled, true, false);

?>
<div class="custom-body auto-redirect">
    <p class="page__paragraph">
        <?php _e( "Would you like to redirect users to their last visited page upon logging in?", "redirect-redirection" ); ?>
    </p>
    <div class="page__custom-flex">
        <div class="page__custom">
            <div class="page__switch-group page-switch-group">
                <span class="page-switch-group__label">
                    <?php _e("Redirect to the last visited page after login", "redirect-redirection"); ?>
                </span>
                <label for="switch-4" class="custom-switch custom-switch">
                    <input type="checkbox" id="switch-4" class="ir-auto-redirect log-me-where-i-finished" <?php echo $LMWIFStatus; ?>>
                    <div class="custom-switch-slider round">
                        <span class="on">
                            <?php _e("On", "redirect-redirection"); ?>
                        </span>
                        <span class="off">
                            <?php _e("Off", "redirect-redirection"); ?>
                        </span>
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>

