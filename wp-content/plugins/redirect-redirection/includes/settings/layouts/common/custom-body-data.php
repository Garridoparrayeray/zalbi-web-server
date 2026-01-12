<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="custom-body__data">
    <div class="flex-table">
        <div class="flex-table__header">
            <span class="flex-table__heading">
                <input type="checkbox" class="ir-select-all-specific-redirects-chk">
            </span>
            <span class="flex-table__heading">
                <?php _e( "Status", "redirect-redirection" ); ?>
            </span>
            <span class="flex-table__heading">
                <?php _e( "Redirect from…", "redirect-redirection" ); ?>
            </span>
            <span class="flex-table__heading">
                <?php _e( "…to:", "redirect-redirection" ); ?>
            </span>
            <span class="flex-table__heading">
                <?php _e( "Added", "redirect-redirection" ); ?>
            </span>
            <span class="flex-table__heading">
                <?php _e( "Type", "redirect-redirection" ); ?>
            </span>
            <span class="flex-table__heading" title="<?php _e("How many times used", "redirect-redirection"); ?>">
                <?php _e( "Used", "redirect-redirection" ); ?>
            </span>
        </div>

        <div class="flex-table__body">
            <?php
            $args = [
                "offset" => 0,
                "where" => [
                    "condition" => "AND",
                    "clauses" => [
                        ["column" => "type", "value" => $redirectionType, "compare" => "="]
                    ],
                ]
            ];
            $redirects = $this->dbManager->getAll($args);

            $html = $this->helper->buildRedirectsHtml($redirects);
            echo $html;
            ?>
        </div>
        <?php $selectAllClass = ($countPages > 1) ? "" : "ir-hidden"; ?>
        <div class="table-select-all d-block w-100 <?php esc_attr_e($selectAllClass); ?>">
            <input type="hidden" class="ir-redirects-count" value="<?php esc_attr_e($countRedirects); ?>" />
            <button class="table-select-all__button ir-select-all-specific-redirects" data-all-checked="false"><?php _e( "Select all", "redirect-redirection" ); ?></button>
            <span><?php _e( "(also from other pages)", "redirect-redirection" ); ?></span>
        </div>

        <?php $paginationClass = ($countPages > 1) ? "" : " ir-hidden"; ?>
        <div class="custom-pagination <?php esc_attr_e($paginationClass); ?>">
            <?php $this->helper->buildPaginationHtml($countRedirects, $countPages, 0); ?>
        </div>

    </div>
</div>