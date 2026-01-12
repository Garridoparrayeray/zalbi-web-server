<?php
if (!defined("ABSPATH")) {
    exit();
}
$activeTab = empty($_COOKIE["ir_active_tab"]) ? IrrPRedirection::$TABS["specific-url-redirections"] : trim(sanitize_text_field($_COOKIE["ir_active_tab"]));
?>
<div class="custom-container" id="irrp_container_main">
    <div class="page">
        <?php include_once "layouts/common/delete-confirmation-prompt.php"; ?>
        <h1 class="page__heading"><?php _e( "Welcome to Ultimate Redirect!", "redirect-redirection" ); ?></h1>
        <div class="tabs-container">
            <div class="tabs">
                <button class="tabs__button <?php echo ($activeTab === IrrPRedirection::$TABS["specific-url-redirections"]) ? "tabs__button--active" : "" ?>" data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["specific-url-redirections"]); ?>"><?php _e( "Specific URL Redirections", "redirect-redirection" ); ?></button>
                <button class="tabs__button <?php echo ($activeTab === IrrPRedirection::$TABS["redirection-rules"]) ? "tabs__button--active" : "" ?>" data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["redirection-rules"]); ?>"><?php _e( "Redirection Rules", "redirect-redirection" ); ?></button>
                <button class="tabs__button <?php echo ($activeTab === IrrPRedirection::$TABS["redirection-and-404-logs"]) ? "tabs__button--active" : "" ?>" data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["redirection-and-404-logs"]); ?>"><?php _e( "Redirection & 404 Logs", "redirect-redirection" ); ?></button>
                <button class="tabs__button <?php echo ($activeTab === IrrPRedirection::$TABS["automatic-redirects"]) ? "tabs__button--active" : "" ?>" data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["automatic-redirects"]); ?>"><?php _e( "Automatic Redirects", "redirect-redirection" ); ?></button>
                <button class="tabs__button <?php echo ($activeTab === IrrPRedirection::$TABS["change-urls"]) ? "tabs__button--active" : "" ?>" data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["change-urls"]); ?>"><?php _e( "Change URLs", "redirect-redirection" ); ?></button>
            </div>
        </div>
        <div class="page__block">
            <?php
            if ($activeTab === IrrPRedirection::$TABS["redirection-rules"]) {
                include_once "layouts/redirection-rules.php";
            } else if ($activeTab === IrrPRedirection::$TABS["redirection-and-404-logs"]) {
                include_once "layouts/redirection-and-404-logs.php";
            } else if ($activeTab === IrrPRedirection::$TABS["automatic-redirects"]) {
                include_once "layouts/automatic-redirects.php";
            } else if ($activeTab === IrrPRedirection::$TABS["change-urls"]) {
                include_once "layouts/change-urls.php";
            } else {
                include_once "layouts/specific-url-redirections.php";
            }
            ?>
        </div>
    </div>
    <jdiv class="label_e50 _bottom_ea7 notranslate" id="irrp_support_chat" style="background: linear-gradient(95deg, rgb(47, 50, 74) 20%, rgb(66, 72, 103) 80%);right: 30px;bottom: 0px;width: 310px;">
      <jdiv class="hoverl_bc6"></jdiv>
      <jdiv class="text_468 _noAd_b4d contentTransitionWrap_c73" style="font-size: 15px;font-family: Arial, Arial;font-style: normal;color: rgb(240, 241, 241);position: absolute;top: 8px;line-height: 13px;">
        <span><?php _e('Connect with support (click to load)', 'redirect-redirection') ?></span><br>
        <span style="color: #eee;font-size: 10px;">
          <?php _e('This will establish connection to the chat servers', 'redirect-redirection'); ?>
        </span>
      </jdiv>
      <jdiv class="leafCont_180">
        <jdiv class="leaf_2cc _bottom_afb">
          <jdiv class="cssLeaf_464"></jdiv>
        </jdiv>
      </jdiv>
    </jdiv>
</div>
<div style="margin-top: 90px;">
<?php do_action('ins_global_print_carrousel'); ?>
</div>
