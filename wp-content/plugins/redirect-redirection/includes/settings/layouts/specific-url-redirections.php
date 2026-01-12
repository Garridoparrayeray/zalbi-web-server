<?php
if (!defined("ABSPATH")) {
    exit();
}

$redirectionType = self::TYPE_REDIRECTION;
$args = ["type" => $redirectionType];
$countRedirects = (int) $this->dbManager->getCount($args);
$countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
$settingsData = $this->getData();

//==== export/import start
$adminUrl = admin_url("/admin-post.php?action=irrp_export&_type=" . self::TYPE_REDIRECTION);
$action = IRRPHelper::nonceKey();
$exportNonceUrl = wp_nonce_url($adminUrl, $action, "_irrp_nonce");
//==== export/import end
?>
<?php include_once "specific-url-redirections/header.php"; ?>
<?php $customBodyClass = ($countRedirects > 0) ? "" : "ir-hidden"; ?>
<div class="ir-import-redirects-container">
    <?php $irImportLinkCls = $countRedirects ? "ir-hidden" : ""; ?>
    <span class="ir-import-redirects-container__bottom-note highlighted ir-import-redirects <?php echo $irImportLinkCls; ?>">
        <?php _e("...or", "redirect-redirection"); ?>
        <label for="irrp_import_redirects">
            <?php _e("<strong>import</strong> ", "redirect-redirection"); ?>
            <input type="file" name="import" id="irrp_import_redirects" data-nonce="<?php echo wp_create_nonce(md5(ABSPATH . get_home_url())); ?>" />
        </label>
        <?php _e("a list of specific URL redirections", "redirect-redirection"); ?>
    </span>
</div>

<div class="custom-body <?php esc_attr_e($customBodyClass); ?>">
    <h3 class="custom-body__heading">
        <span class="custom-body__heading-primary"><?php _e("Your redirects", "redirect-redirection"); ?></span>
        <span class="custom-body__heading-secondary"><?php _e("(specific URL redirects only)", "redirect-redirection"); ?></span>
    </h3>
    <?php include_once "common/custom-filter.php"; ?>
    <?php include_once "common/custom-body-data.php"; ?>
    <textarea class="ir-selected-redirects ir-hidden"></textarea>
    <input type="hidden" class="ir-redirection-type" value="<?php esc_attr_e(self::TYPE_REDIRECTION); ?>" />
</div>