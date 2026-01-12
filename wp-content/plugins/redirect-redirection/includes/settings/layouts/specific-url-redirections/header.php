<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="header ir-header">
    <h2 class="header__heading ir-header__heading"><?php _e("Add a redirect", "redirect-redirection"); ?></h2>
    <div class="header__flex" id="ir_hedaer_flex">
        <div class="header__input-group input-group">
            <label class="input-group__label"><?php
                printf(
                        __('Redirect from the %1$sspecific URL%2$s…', 'redirect-redirection'),
                        '<strong class="strong">',
                        '</strong>'
                );
                ?></label>
            <input class="input-group__input w-auto ir-redirect-from" type="text" placeholder="<?php _e("Enter the URL you want to redirect", "redirect-redirection"); ?>">
        </div>
        <div class="header__arrow-svg">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.829 9.5547L13.8293 2.88839C13.7013 2.74706 13.5227 2.66707 13.3334 2.66707H9.33358C9.07093 2.66707 8.83227 2.82173 8.72428 3.06171C8.61762 3.30303 8.66162 3.58435 8.83761 3.77901L14.436 10L8.83761 16.2197C8.66162 16.4157 8.61628 16.697 8.72428 16.937C8.83227 17.1783 9.07093 17.3329 9.33358 17.3329H13.3334C13.5227 17.3329 13.7013 17.2516 13.8293 17.113L19.829 10.4466C20.057 10.1933 20.057 9.80668 19.829 9.5547Z" fill="currentColor" />
                <path d="M11.1628 9.5547L5.16314 2.88839C5.03514 2.74706 4.85649 2.66707 4.66716 2.66707H0.66738C0.404728 2.66707 0.166074 2.82173 0.0580799 3.06171C-0.048581 3.30303 -0.00458339 3.58435 0.171407 3.77901L5.76977 10L0.171407 16.2197C-0.00458339 16.4157 -0.0499143 16.697 0.0580799 16.937C0.166074 17.1783 0.404728 17.3329 0.66738 17.3329H4.66716C4.85649 17.3329 5.03514 17.2516 5.16314 17.113L11.1628 10.4466C11.3908 10.1933 11.3908 9.80668 11.1628 9.5547Z" fill="currentColor" />
            </svg>
        </div>
        <div class="header__input-group input-group">
            <label class="input-group__label"><?php
                printf(
                        __('…to a %1$sspecific URL:​%2$s', 'redirect-redirection'),
                        '<strong class="strong">',
                        '</strong>'
                );
                ?></label>
            <input class="input-group__input w-auto ir-redirect-to" type="text" placeholder="<?php _e("Enter the URL you want to redirect to", "redirect-redirection"); ?>">
        </div>
    </div>
    <?php include_once IRRP_DIR_PATH . "/includes/settings/layouts/common/header-settings-paragraph.php"; ?>   
    <?php include_once IRRP_DIR_PATH . "/includes/settings/layouts/common/default-settings-modal.php";?>
    <div class="header__call-to-action cta">
        <button class="cta__button ir-add-specific-redirect" data-db-id="0">
            <?php _e("Add this redirect!", "redirect-redirection"); ?>
        </button>
        <!-- <button class="cta__cancel-btn ir-header-cancel"> -->
            <?php
            //  _e("Cancel", "redirect-redirection"); 
             ?>
        <!-- </button>                -->
    </div> 
</div>