<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div class="header ir-header">
    <h2 class="header__heading ir-header__heading"><?php _e("Add a redirection rule", "redirect-redirection"); ?></h2>
    <div class="ir-rules-container ir-rules-form">
        <div class="header__flex ir-header-flex" id="ir_hedaer_flex">
            <div class="header__input-group header__input-group--1 input-group ir-criterias">
                <label class="input-group__label"><?php
                    printf(
                            __('Redirect %1$sall%2$s URLs which:', 'redirect-redirection'),
                            '<strong>',
                            '</strong>'
                    );
                    ?></label>
                <div class="header__flex-inputs">
                    <?php
                    $selected = "false";
                    IRRPHelper::customDropdown("criteria", IrrPRedirection::$CRITERIAS, $selected);
                    ?>
                    <div class="header__2nd-dropdown-container ir-criteria-value-dd">
                        <?php
                        $selected = "false";
                        IRRPHelper::customDropdown("criteria_value_dd", IrrPRedirection::$PERMALINK_STRUCTURE_VALUES, $selected);
                        ?>
                    </div>
                    <div class="input-wrapper">
                        <input class="input-group__input flex-grow-1 ir-criteria-value ir-redirect-from ir-reload-clear" type="text" name="criteria_value" placeholder="<?php _e("Enter the string", "redirect-redirection"); ?>">
                        <div class="regex-help">Need help with Regex? <a href="https://wordpress.org/support/plugin/redirect-redirection/" target="_blank" class="ask-us">Ask us</a></div>
                    </div>
                </div>
            </div>
            <div class="header__arrow-svg">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M19.829 9.5547L13.8293 2.88839C13.7013 2.74706 13.5227 2.66707 13.3334 2.66707H9.33358C9.07093 2.66707 8.83227 2.82173 8.72428 3.06171C8.61762 3.30303 8.66162 3.58435 8.83761 3.77901L14.436 10L8.83761 16.2197C8.66162 16.4157 8.61628 16.697 8.72428 16.937C8.83227 17.1783 9.07093 17.3329 9.33358 17.3329H13.3334C13.5227 17.3329 13.7013 17.2516 13.8293 17.113L19.829 10.4466C20.057 10.1933 20.057 9.80668 19.829 9.5547Z"
                        fill="currentColor" />
                    <path
                        d="M11.1628 9.5547L5.16314 2.88839C5.03514 2.74706 4.85649 2.66707 4.66716 2.66707H0.66738C0.404728 2.66707 0.166074 2.82173 0.0580799 3.06171C-0.048581 3.30303 -0.00458339 3.58435 0.171407 3.77901L5.76977 10L0.171407 16.2197C-0.00458339 16.4157 -0.0499143 16.697 0.0580799 16.937C0.166074 17.1783 0.404728 17.3329 0.66738 17.3329H4.66716C4.85649 17.3329 5.03514 17.2516 5.16314 17.113L11.1628 10.4466C11.3908 10.1933 11.3908 9.80668 11.1628 9.5547Z"
                        fill="currentColor" />
                </svg>
            </div>
            <div class="header__input-group input-group ir-actions">
                <label class="input-group__label">
                    <?php _e("…to:​", "redirect-redirection"); ?>
                </label>
                <div class="header__flex-inputs header__flex-inputs--disabled">
                    <?php
                    $selected = "false";
                    IRRPHelper::customDropdown("action", IrrPRedirection::$ACTIONS, $selected);
                    ?>
                    <div class="header__2nd-dropdown-container ir-action-value-dd">
                        <?php
                        $selected = "false";
                        IRRPHelper::customDropdown("action_value_dd", IrrPRedirection::$PERMALINK_STRUCTURE_VALUES, $selected);
                        ?>
                    </div>
                    <input class="input-group__input flex-grow-1 ir-action-value ir-redirect-to ir-reload-clear" type="text" name="action_value" placeholder="<?php _e("Enter the URL", "redirect-redirection"); ?>" />
                </div>
            </div>
        </div>
    </div>

    <!-- CLONE --START -->
    <div class="header__flex ir-header-flex mt-50 mt-1140-10 ir-header-flex-as-placeholder header__flex--hidden-placeholder">
        <div class="header__input-group header__input-group--1 input-group ir-criterias">
            <div class="header__flex-inputs">
                <?php
                $selected = "false";
                IRRPHelper::customDropdown("criteria", IrrPRedirection::$CRITERIAS, $selected);
                ?>
                <div class="header__2nd-dropdown-container ir-criteria-value-dd">
                    <?php
                    $selected = "false";
                    IRRPHelper::customDropdown("criteria_value_dd", IrrPRedirection::$PERMALINK_STRUCTURE_VALUES, $selected);
                    ?>
                </div>
                <input class="input-group__input flex-grow-1 ir-criteria-value ir-redirect-from ir-reload-clear" type="text" name="criteria_value" placeholder="<?php _e("Enter the string", "redirect-redirection"); ?>">
            </div>
        </div>
        <button onclick="window.removeRule(event)" class="header__close-svg">
            <svg width="17" height="17" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.5118 2.92687C12.8188 2.63009 13.044 2.29238 13.0951 1.85232C13.0951 1.84209 13.1054 1.83185 13.1156 1.82162V1.47367C13.0747 1.3304 13.044 1.17689 12.9928 1.04385C12.7472 0.470756 12.3174 0.13304 11.7033 0.0307015C11.6829 0.0307015 11.6522 0.0102338 11.6317 0.0102338H11.2735C11.2223 0.0204676 11.1712 0.0409353 11.12 0.0511691C10.772 0.122806 10.4753 0.307014 10.2194 0.552626C9.04253 1.71928 7.86564 2.89617 6.67852 4.06283C6.64782 4.08329 6.61712 4.13446 6.58642 4.1754C6.56595 4.18563 6.54548 4.18563 6.51478 4.19586C6.49431 4.1447 6.47384 4.09353 6.43291 4.05259C5.26625 2.89617 4.0996 1.73975 2.93294 0.573094C2.63616 0.276313 2.29845 0.0614029 1.86863 0.0102338C1.86863 0.0204676 1.85839 0.0102338 1.84816 0H1.48997C1.316 0.0511691 1.14202 0.0818705 0.978283 0.153507C-0.0450983 0.614029 -0.331645 1.94443 0.435891 2.7529C1.17273 3.52043 1.94026 4.25727 2.68733 5.00434C3.16832 5.47509 3.63908 5.93561 4.10983 6.40637C4.14053 6.43707 4.17123 6.46777 4.2224 6.52918C4.17123 6.55988 4.12007 6.58034 4.08936 6.62128C2.90224 7.78793 1.72535 8.96482 0.538229 10.1315C-0.00416301 10.6739 -0.147436 11.4005 0.159578 12.0554C0.630334 13.0481 1.96073 13.3142 2.7692 12.5671C3.05575 12.301 3.33206 12.0247 3.60837 11.7484C4.54989 10.8171 5.4914 9.88587 6.43291 8.94436C6.46361 8.91365 6.49431 8.86249 6.51478 8.82155C6.53525 8.82155 6.55571 8.82155 6.57618 8.82155C6.61712 8.87272 6.64782 8.93412 6.69899 8.98529C7.87588 10.1519 9.04253 11.3186 10.2194 12.475C10.772 13.0174 11.4986 13.1505 12.1638 12.823C12.6755 12.5774 12.9723 12.1578 13.0747 11.6051C13.0849 11.5744 13.0951 11.554 13.1054 11.5233V11.1651C13.0951 11.1139 13.0747 11.0628 13.0644 11.0116C12.9928 10.6636 12.8086 10.3771 12.5527 10.1212C11.3758 8.96482 10.2092 7.79817 9.0323 6.63151C9.0016 6.60081 8.95043 6.55988 8.89926 6.51894C8.95043 6.46777 8.98113 6.42684 9.02206 6.39614C10.1887 5.23971 11.3451 4.08329 12.5118 2.92687Z" fill="#D0302D"></path> </svg>
        </button>
    </div>
    <!-- CLONE --END -->

    <div class="text-center text-1140-initial">
        <button class="header__inline-button ir-add-another-criteria">
            <?php _e("+ Add another criteria", "redirect-redirection"); ?>
        </button>
        <!--<input type="hidden" class="ir-criteria-count" value="1">-->
    </div>
    <?php include_once "rules-explanation.php"; ?>
    <?php include_once IRRP_DIR_PATH . "/includes/settings/layouts/common/header-settings-paragraph.php"; ?>
    <?php include_once IRRP_DIR_PATH . "/includes/settings/layouts/common/default-settings-modal.php";?>
    <div class="header__call-to-action cta">
        <button class="cta__button cta__button--large ir-add-redirect-rule" data-db-id="0">
            <?php _e("Add this redirection rule!", "redirect-redirection"); ?>
        </button>
        <!-- <button class="cta__cancel-btn ir-header-cancel"> -->
            <?php 
            // _e("Cancel", "redirect-redirection"); 
            ?>
        <!-- </button>         -->
    </div>
</div>
