<?php
if (!defined("ABSPATH")) {
    exit();
}
$attributes = empty($args["attributes"]) ? false : $args["attributes"];
$attributesStr = "";
$classes = empty($args["classes"]) ? "" : implode(" ", $args["classes"]);
$isMultiple = empty($args["isMultiple"]) ? false : $args["isMultiple"];
if ($attributes) {
    foreach ($attributes as $attrKey => $attrValue) {
        $attributesStr .= " " . esc_attr($attrKey) . "='" . esc_attr($attrValue) . "'";
    }
}
if ($isMultiple && $selected != "false"){
    $selected = json_encode($selected);
}
?>
<div class="custom-modal__custom-dropdown custom-dropdown <?php esc_attr_e($classes); ?>" data-name="<?php esc_attr_e($ddName); ?>" <?php esc_attr_e($attributesStr); ?> data-default-selected="<?php esc_attr_e($selected); ?>" data-multiple=<?php echo $isMultiple ? "true" : "false"; ?>>
    <input name="<?php esc_attr_e($ddName); ?>" class="ir-custom-dropdown-value ir-<?php esc_attr_e($ddName); ?>" type="hidden" value=""/>
    <span tabindex="1" role="button" class="custom-dropdown__toggle-btn custom-dropdown-toggle">
        <span data-selected-dropdown-item-id="<?php esc_attr_e($selected); ?>" class="custom-dropdown-toggle__text"><?php _e("Select Option", "redirect-redirection"); ?></span>
        <span class="custom-dropdown-toggle__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="11" viewBox="0 0 17 11" fill="none">
                <path d="M2 2L8.17647 8.5L15 2" stroke="currentcolor" stroke-width="3" />
            </svg>
        </span>
    </span>
    <ul class="custom-dropdown__ul">
        <?php
        foreach ($ddOptions as $key => $value) {
            if (is_array($value)) {
                $optionDisabled = isset($value["status"]) ? esc_attr($value["status"]) : "";
                ?>
                <li data-dropdown-item-id="<?php esc_attr_e($key); ?>" class="custom-dropdown__li" data-value="<?php esc_attr_e($value["option"]); ?>" data-status="<?php echo $optionDisabled; ?>">
                    <span class="custom-dropdown__li-content"><?php esc_attr_e($value["text"]); ?></span>
                </li>
                <?php
            }
        }
        ?>
    </ul>
</div>
