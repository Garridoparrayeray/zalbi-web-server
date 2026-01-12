<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="redirect-content__table-wrap">
    <div class="redirect-table">
        <table class="redirect-table">
            <thead>
                <tr>
                    <th><?php esc_html_e("URL visitors tried to access", "redirect-redirection"); ?></th>
                    <th><?php esc_html_e("URL where they landed", "redirect-redirection"); ?></th>
                    <th><?php esc_html_e("Date & Time", "redirect-redirection"); ?></th>
                    <th><?php esc_html_e("Type", "redirect-redirection"); ?></th>
                    <th><?php esc_html_e("Count", "redirect-redirection"); ?></th>
                    <th>
                        <div class="redirect-table__cell-select">
                            <span><?php esc_html_e("Show", "redirect-redirection"); ?></span>
                            <?php
                            $selected = isset(IrrPRedirection::$REDIRECTION_LOGS_FILTER["selectedId"]) ? (int) IrrPRedirection::$REDIRECTION_LOGS_FILTER["selectedId"] : "false";
                            IRRPHelper::customDropdown("redirection_logs_filter", IrrPRedirection::$REDIRECTION_LOGS_FILTER, $selected);
                            ?>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="ir-redirect-table-tbody">
                <?php echo $this->helper->buildLogsHtml($logs); ?>
            </tbody>
        </table>
        <?php if ($countPages > 1) { ?>
            <div class="text-center">
                <a class="redirect-table__show-more ir-show-more" data-offset="1" data-max-offset="<?php echo $countPages; ?>"> 
                    <?php esc_html_e("Show more", "redirect-redirection"); ?>
                    <svg class="ir-header-settings-arrow ir-header-settings-arrow--down" xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="24" height="24"><path d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z"/></svg>
                </a>
            </div>
        <?php } ?>
    </div>

</div>