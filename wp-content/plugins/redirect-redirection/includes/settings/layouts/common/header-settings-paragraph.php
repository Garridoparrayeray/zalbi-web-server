<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="header__paragraph ir-settings-paragraph">
    <span class="header-settings">
    <?php
      $defaultSettingsSerialized = maybe_serialize($this->getDefaultSettings());
      $userSettingsSerialized = maybe_serialize($this->getData());
      $isDefault = md5($defaultSettingsSerialized) === md5($userSettingsSerialized);
      $none = ' style="display: none;"';
    ?>
    <span id="ir-default-settings-text"<?php echo (!$isDefault ? $none : '') ?>>
      <span><?php _e( "…with the ", "redirect-redirection" ); ?></span>
      <strong tabindex="1" id="ir-flexible-aos-text"><?php _e( "default settings", "redirect-redirection" ); ?></strong>
      <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn">
          <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="info icon">
          <p class="custom-modal-info-btn__tooltip cmib-tooltip--1">
              Redirect HTTP code 301 Moved Permanently
          </p>
      </span>
    </span>

    <span id="ir-tailored-settings-text"<?php echo ($isDefault ? $none : '') ?>>
      <span><?php _e( "…with your ", "redirect-redirection" ); ?></span>
      <strong tabindex="1" id="ir-flexible-aos-text"><?php _e( "tailored settings", "redirect-redirection" ); ?></strong>
    </span>

    </span>

    <!-- Toggle Advanced Section -->
    <span role="button" class="highlighted ir-default-settings">
        <?php _e( "Show advanced options", "redirect-redirection" ); ?>
        <svg class="ir-header-settings-arrow ir-header-settings-arrow--down" xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="24" height="24"><path d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z"/></svg>
    </span>
    <!-- End Toggle Advanced Section -->

</div>
