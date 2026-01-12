<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="custom-filter">
    <form class="custom-filter__select-panel justify-content-center ir-all-selected-form ir-hidden">
        <span class="custom-filter__group-label"><?php _e("For all selected:", "redirect-redirection"); ?></span>
        <div class="custom-filter__radio custom-filter-input-group">
            <input id="action-1" checked name="select-action" type="radio" class="custom-filter-input-group__radio ir-redirect-bulk-edit" value="1">
            <label for="action-1" class="custom-filter-input-group__label"><?php _e("Enable", "redirect-redirection"); ?></label>
        </div>

        <div class="custom-filter__radio custom-filter-input-group">
            <input id="action-2" name="select-action" type="radio" class="custom-filter-input-group__radio ir-redirect-bulk-edit" value="0">
            <label for="action-2" class="custom-filter-input-group__label"><?php _e("Disable", "redirect-redirection"); ?></label>
        </div>

        <div class="custom-filter__radio custom-filter-input-group">
            <input id="action-3" name="select-action" type="radio" class="custom-filter-input-group__radio ir-redirect-bulk-edit" value="-1">
            <label for="action-3" class="custom-filter-input-group__label custom-filter-input-group__label--danger"><?php _e("Delete", "redirect-redirection"); ?></label>
        </div>

        <div class="custom-filter__submit-btn-container custom-filter-input-group">
            <button class="custom-filter-input-group__submit-btn ir-action-for-all-selected" type="submit">
                <?php _e("Go!", "redirect-redirection"); ?>
            </button>
        </div>
    </form>

    <?php $exportNonceUrl = empty($exportNonceUrl) ? "#!" : $exportNonceUrl; ?>

    <p class="custom-filter__links">
        <label for="irrp_import_redirects">
            <?php _e("Import list", "redirect-redirection"); ?>
            <input type="file" name="import" id="irrp_import_redirects" data-nonce="<?php echo wp_create_nonce(md5(ABSPATH . get_home_url())); ?>" />
        </label>
        <span style="margin: 0 5px"> | </span>
        <a href="<?php echo $exportNonceUrl; ?>" class="custom-filter__link"><?php _e("Export list", "redirect-redirection"); ?></a>
    </p>    

    <form class="custom-filter__search-input custom-filter-search-input ir-live-search-form">
        <input type="search" placeholder="<?php _e("Search", "redirect-redirection"); ?>" class="custom-filter-search-input__input ir-live-search ir-reload-clear">
        <button class="custom-filter-search-input__submit-btn ir-live-search-btn">
            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/loop-icon.svg"); ?>" alt="<?php _e("search button's icon", "redirect-redirection"); ?>">
        </button>
    </form>
</div>