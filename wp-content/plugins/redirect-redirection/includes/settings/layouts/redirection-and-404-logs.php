<?php
if (!defined("ABSPATH")) {
    exit();
}

$logs = $this->dbManager->logGet();

$args = ["count" => true];
$countLogs = $this->dbManager->logGet($args);

$logsPerPage = ($lpp = ((int) apply_filters("irrp_logs_per_page", self::PER_PAGE_LOGS))) > 0 ? $lpp : self::PER_PAGE_LOGS;

$countPages = ceil($countLogs / $logsPerPage);

$status = checked(get_option(self::OPTIONS_LOGS_STATUS), true, false);
?>
<?php include_once "redirection-and-404-logs/header.php"; ?>
<!-- redirect-content--empty -->
<div class="redirect-content">
    <!-- <section class="redirect-content redirect-content--empty"> -->
    <div class="redirect-content ir-redirection_logs_title">
        <h2 class="redirect-content__title"><?php esc_html_e("Redirection & 404 logs", "redirect-redirection"); ?></h2>
        <span class="me-30">
          <span class="page__info_title irrp_logs_title">
            <?php esc_html_e("Toggle logging", "redirect-redirection"); ?>
            <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn irrp_logs">
                <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                <p class="custom-modal-info-btn__tooltip cmib-tooltip--1">
                    <?php _e("If activated, the system will store all logs related to 404 errors and redirects in the database. If deactivated, no logs will be saved.", "redirect-redirection"); ?>
                </p>
            </span>
          </span>
            <label for="switch-log-status" class="custom-switch">
                <input type="checkbox"
                    id="switch-log-status" <?php esc_attr_e( $status ); ?> class="ir-redirection_logs_status" >
                    <div class="custom-switch-slider round">
                        <span class="on"><?php _e( "On", "redirect-redirection" ); ?></span>
                        <span class="off"><?php _e( "Off", "redirect-redirection" ); ?></span>
                    </div>
            </label>
        </span>
    </div>
    <?php if ($countLogs) { ?>
        <?php include_once "redirection-and-404-logs/actions.php"; ?>
        <?php include_once "redirection-and-404-logs/data-table.php"; ?>        
    <?php } else { ?>
        <h2 class="redirect-content__title redirect-content__title--no-logs___changed"><?php esc_html_e("No logs yet", "redirect-redirection"); ?></h2>
    <?php } ?>   
</div>