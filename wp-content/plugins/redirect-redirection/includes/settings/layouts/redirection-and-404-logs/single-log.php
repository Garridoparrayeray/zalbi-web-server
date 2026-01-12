<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<tr class="<?php echo $is404Cls; ?>" data-log-id="<?php echo $log_id; ?>">
    <td class="redirect-table__url-cell">
        <input type="text" class="redirect-table__url-item table-input-group__input ir-scroll-to-right" value="<?php esc_attr_e($log_accessed); ?>" readonly=""/>
    </td>
    <td class="redirect-table__url-cell">
        <input type="text" class="redirect-table__url-item table-input-group__input ir-scroll-to-right" value="<?php esc_attr_e($log_landed); ?>" readonly=""/>
    </td>
    <td><?php echo date("d/m/y H:i", $log_request_timestamp); ?></td>
    <?php if ($log_log_code === self::LOGCODE_IS_NOT_404_REDIRECT) { ?>
        <td><?php echo $log_response_code; ?></td>
        <td></td>
    <?php } else if ($log_log_code === self::LOGCODE_IS_404_NO_REDIRECT) { ?>
        <?php if ($redirect_request_timestamp && $redirect_request_timestamp > $log_request_timestamp) { ?>
            <td><?php echo $log_response_code; ?></td>
            <td><?php echo $log_count; ?></td>
            <td class="redirect-table__text-gray"><?php esc_html_e("(fixed for the future)", "redirect-redirection"); ?></td>
        <?php } else { ?>
            <td><?php echo $log_response_code; ?></td>
            <td><?php echo $log_count; ?></td>
            <td>
                <a href="!#" class="redirect-table__btn-redirect redirect-btn ir-load-tab-add-redirect" 
                   data-tab="<?php esc_attr_e(IrrPRedirection::$TABS["specific-url-redirections"]); ?>"
                   data-request-url="<?php esc_attr_e($log_request_url); ?>">
                    <span><?php esc_html_e("Redirect", "redirect-redirection"); ?></span>
                    <svg width="10" height="10" id="icon-redirect-arrows" viewBox="0 0 10 10">
                    <g clip-path="url(#clip0_43_17)">
                    <path d="M5.58141 4.77725L2.58157 1.4441C2.51757 1.37343 2.42824 1.33344 2.33358 1.33344H0.33369C0.202364 1.33344 0.083037 1.41076 0.0290399 1.53076C-0.0242905 1.65142 -0.00229169 1.79208 0.0857035 1.8894L2.88489 4.9999L0.0857035 8.10974C-0.00229169 8.20773 -0.0249571 8.34839 0.0290399 8.46838C0.083037 8.58904 0.202364 8.66637 0.33369 8.66637H2.33358C2.42824 8.66637 2.51757 8.62571 2.58157 8.55638L5.58141 5.22323C5.6954 5.09657 5.6954 4.90324 5.58141 4.77725Z" fill="white" />
                    <path d="M9.91454 4.77725L6.9147 1.4441C6.8507 1.37343 6.76137 1.33344 6.66671 1.33344H4.66682C4.53549 1.33344 4.41617 1.41076 4.36217 1.53076C4.30884 1.65142 4.33084 1.79208 4.41883 1.8894L7.21802 4.9999L4.41883 8.10974C4.33084 8.20773 4.30817 8.34839 4.36217 8.46838C4.41617 8.58904 4.53549 8.66637 4.66682 8.66637H6.66671C6.76137 8.66637 6.8507 8.62571 6.9147 8.55638L9.91454 5.22323C10.0285 5.09657 10.0285 4.90324 9.91454 4.77725Z" fill="white" />
                    </g>
                    </svg>
                </a>
            </td>
        <?php } ?>        
    <?php } else if ($log_log_code === self::LOGCODE_IS_404_REDIRECT) { ?>
        <td><?php echo "{$log_request_code} => {$log_response_code}"; ?></td>
        <td></td>
    <?php } else { ?>
        <td><?php echo $log_request_code; ?></td>
        <td></td>
    <?php } ?>
</tr>