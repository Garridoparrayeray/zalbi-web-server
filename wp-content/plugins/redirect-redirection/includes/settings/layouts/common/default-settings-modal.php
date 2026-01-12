<?php
if (!defined("ABSPATH")) {
    exit();
}
// $settingsData initialized in ajax functions
?>
<div id="ir-default-settings-form" style="display:none">

    <div class="custom-modal">
        <div class="custom-modal__box settings-box">
            <form action="" class="ir-default-settings-form">
                <div class="settings-box__container">
                    <h6 class="settings-box__heading sett-box-heading">
                        <span class="sett-box-heading__icon"><img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/cogs-icon.svg"); ?>" alt="<?php _e( "cogs icon", "redirect-redirection" ); ?>"></span>
                        <span class="sett-box-heading__text"><?php _e( "Advanced options", "redirect-redirection" ); ?></span>
                    </h6>

                    <div class="settings-box__checkboxes-container checkboxes-container">
                        <div class="checkboxes-container__col">
                            <span class="checkboxes-container__label"><?php _e( "Source URL", "redirect-redirection" ); ?></span>
                        </div>
                        <div class="checkboxes-container__col checkboxes-rows">
                            <?php
                            $ignoreTrailingSlashes = (empty($settingsData["ignore_trailing_slashes"])) ? 0 : (int) $settingsData["ignore_trailing_slashes"];
                            $ignoreTrailingSlashesDisabled = "";
                            $ignoreTrailingSlashesStyle = "";
                            if (isset($id) && !empty($settingsData[self::META_KEY_CRITERIAS][$id]["criteria"]) && ($settingsData[self::META_KEY_CRITERIAS][$id]["criteria"] === "regex-match")) {
                                $ignoreTrailingSlashesDisabled = "disabled='disabled'";
                                $ignoreTrailingSlashesStyle = "style='opacity:0.7;z-index:9999;'";
                            }
                            ?>
                            <div class="checkboxes-rows__row" <?php esc_attr_e($ignoreTrailingSlashesStyle); ?>>
                                <input id="check-1" class="checkboxes-rows__input" type="checkbox" name="ignore_trailing_slashes" value="1" <?php echo checked($ignoreTrailingSlashes === 1); ?> <?php esc_attr_e($ignoreTrailingSlashesDisabled); ?>>
                                <label for="check-1" class="checkboxes-rows__text"><?php _e( "Ignore trailing slashes​", "redirect-redirection" ); ?></label>
                                <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn">
                                    <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                    <p class="custom-modal-info-btn__tooltip cmib-tooltip--1">
                                        <?php _e( "If checked, URLs with “/” at the end and those without it will be redirected.", "redirect-redirection" ); ?>
                                    </p>
                                </span>
                            </div>
                            <div class="checkboxes-rows__row">
                                <?php $ignoreParameters = (empty($settingsData["ignore_parameters"])) ? 0 : (int) $settingsData["ignore_parameters"]; ?>
                                <input id="check-2" class="checkboxes-rows__input" type="checkbox" name="ignore_parameters" value="1" <?php echo checked($ignoreParameters === 1); ?>>
                                <label for="check-2" class="checkboxes-rows__text"><?php _e( "Ignore parameters​", "redirect-redirection" ); ?></label>
                                <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn">
                                    <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                    <p class="custom-modal-info-btn__tooltip cmib-tooltip--2">
                                        <?php _e( "If checked URLs with parameters will be redirected as well, e.g. if you selected /whatever to get redirected, then /whatever?param will be redirected too.", "redirect-redirection" ); ?>
                                    </p>
                                </span>
                            </div>
                        </div>
                        <div class="checkboxes-container__col checkboxes-rows">
                            <div class="checkboxes-rows__row">
                                <?php $ignoreCase = (empty($settingsData["ignore_case"])) ? 0 : (int) $settingsData["ignore_case"]; ?>
                                <input id="check-3" class="checkboxes-rows__input" type="checkbox" name="ignore_case" value="1" <?php echo checked($ignoreCase === 1); ?>>
                                <label for="check-3" class="checkboxes-rows__text"><?php _e( "Ignore lower/upper case​", "redirect-redirection" ); ?></label>
                                <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn">
                                    <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                    <p class="custom-modal-info-btn__tooltip cmib-tooltip--3">
                                        <?php _e( "If checked the cases of your source URL will be ignored, e.g. if you entered /whatever as URL, /WhAteVER will also be redirected.", "redirect-redirection" ); ?>
                                    </p>
                                </span>
                            </div>
                            <div class="checkboxes-rows__row">
                                <?php $passOnParameters = (empty($settingsData["pass_on_parameters"])) ? 0 : (int) $settingsData["pass_on_parameters"]; ?>
                                <input id="check-4" class="checkboxes-rows__input" type="checkbox" name="pass_on_parameters" value="1" <?php echo checked($passOnParameters === 1); ?>>
                                <label for="check-4" class="checkboxes-rows__text"><?php _e( "Pass on parameters​", "redirect-redirection" ); ?></label>
                                <span role="button" tabindex="1" class="ml-0 custom-modal__info-btn custom-modal-info-btn">
                                    <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                    <p class="custom-modal-info-btn__tooltip cmib-tooltip--4">
                                        <?php _e( "If checked parameters will be passed on, e.g. if you selected to redirect /whatever to /whatever-new, then when a user enters /whatever?param he will be redirected to /whatever-new?param.", "redirect-redirection" ); ?>
                                    </p>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="settings-box__table settings-table settings-table--with-border" style="border-bottom: 0;">
                        <div class="settings-table__col settings-table__col--first-child d-flex align-items-center">
                            <span class="settings-table__label"><?php _e( "Set Redirect Header:​", "redirect-redirection" ); ?></span>
                            <span role="button" tabindex="1" class="mb-21 custom-modal__info-btn custom-modal__info-btn--small-devices ir-http-codes-show">
                                <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                <p class="custom-modal-info-btn__tooltip cmib-tooltip--11">
                                    <?php _e("Set Header that will be passed with that redirection, you can receive it at your destination URL and handle some action.​ i.e. fieldKey: fieldValue", "redirect-redirection" ); ?>
                                </p>
                            </span>
                        </div>
                        <span class="checkboxes-rows__row d-flex align-items-center">
                            <input class="rules-table-input-group__input w-1140-400px" type="text" placeholder="header:value,header2:value..." name="redirection_http_headers" value="<?php esc_attr_e(isset($settingsData["redirection_http_headers"]) ? $settingsData["redirection_http_headers"] : ''); ?>">
                            <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                <p class="custom-modal-info-btn__tooltip cmib-tooltip--11">
                                    <?php _e("Set Header that will be passed with that redirection, you can receive it at your destination URL and handle some action.​ i.e. fieldKey: fieldValue", "redirect-redirection" ); ?>
                                </p>
                            </span>
                        </span>
                    </div>

                    <div class="settings-box__table settings-table settings-table--with-border">
                        <div class="settings-table__col settings-table__col--first-child d-flex align-items-center">
                            <span class="settings-table__label"><?php _e( "Redirect HTTP code:​", "redirect-redirection" ); ?></span>
                            <span role="button" tabindex="1" class="mb-21 custom-modal__info-btn custom-modal__info-btn--small-devices ir-http-codes-show">
                                <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                <p class="custom-modal-info-btn__tooltip cmib-tooltip--16">
                                    <?php _e( "Click to see an explanation of HTTP codes", "redirect-redirection" ); ?>
                                </p>
                            </span>
                        </div>
                        <div class="settings-table__col d-flex align-items-center">
                            <?php
                            $redirectCode = (empty($settingsData["redirect_code"])) ? 0 : (int) $settingsData["redirect_code"];
                            $ddOptions = [
                                [
                                    "option" => 301,
                                    "text"   => __( "301 - Moved Permanently", "redirect-redirection" )
                                ],
                                [
                                    "option" => 302,
                                    "text"   => __( "302 – Found", "redirect-redirection" )
                                ],
                                [
                                    "option" => 303,
                                    "text"   => __( "303 – See Other", "redirect-redirection" )
                                ],
                                [
                                    "option" => 304,
                                    "text"   => __( "304 – Not Modified", "redirect-redirection" )
                                ],
                                [
                                    "option" => 307,
                                    "text"   => __( "307 – Temporary Redirect", "redirect-redirection" )
                                ],
                                [
                                    "option" => 308,
                                    "text"   => __( "308 – Permanent Redirect", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($redirectCode, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            IRRPHelper::customDropdown("redirect_code", $ddOptions, $selected);
                            ?>
                            <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices ir-http-codes-show">
                                <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                <p class="custom-modal-info-btn__tooltip cmib-tooltip--5">
                                    <?php _e( "Click to see an explanation of HTTP codes", "redirect-redirection" ); ?>
                                </p>
                            </span>
                        </div>
                    </div>

                    <?php
                    $inclusionExclusionRules = (empty($settingsData["inclusion_exclusion_rules"])) ? 0 : (int) $settingsData["inclusion_exclusion_rules"];
                    $rulesContainerClass = $inclusionExclusionRules ? "" : "ir-hidden";
                    ?>
                    <div class="settings-box__table settings-table" style="<?php echo strpos($rulesContainerClass, "ir-hidden") !== false ? "margin-bottom: 50px" : "" ?>">
                        <div class="settings-table__col settings-table__col--first-child">
                            <span class="settings-table__label"><?php _e( "Inclusion & exclusion rules​", "redirect-redirection" ); ?></span>
                        </div>
                        <div class="settings-table__col">
                            <label for="cron-btn-toggle" class="custom-switch">
                                <input type="checkbox" id="cron-btn-toggle" class="ir-rules-switcher redi_nondef" name="inclusion_exclusion_rules" value="1" <?php echo checked($inclusionExclusionRules === 1); ?>>
                                <div class="custom-switch-slider round">
                                    <span class="on"><?php _e( "On", "redirect-redirection" ); ?></span>
                                    <span class="off"><?php _e( "Off", "redirect-redirection" ); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="settings-box__rules-table rules-table ir-redirect-settings-container <?php esc_attr_e($rulesContainerClass); ?>">
                        <div class="rules-table__row">
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label"><?php
                                printf(
                                    __( 'Only redirect if %1$sall%2$s of the following criteria ​', 'redirect-redirection' ),
                                    '<span class="settings-box__link">',
                                    '</span>'
                                );
                                ?></span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php
                                $redirectOptions = (empty($settingsData["redirect_options"])) ? "" : trim($settingsData["redirect_options"]);
                                $ddOptions = [
                                    [
                                        "option" => "are_case",
                                        "text"   => __( "are the case", "redirect-redirection" )
                                    ],
                                    [
                                        "option" => "are_not_case",
                                        "text"   => __( "are not the case", "redirect-redirection" )
                                    ],
                                ];
                                $selected = ($found = array_search($redirectOptions, array_column($ddOptions, "option"))) !== false ? $found : "false";
                                IRRPHelper::customDropdown("redirect_options", $ddOptions, $selected);
                                ?>
                            </div>
                        </div>

                        <div class="rules-table__row">
                            <?php
                            $rulesGroup1Enabled = (empty($settingsData["rules_group1"]["enabled"])) ? "" : (int) $settingsData["rules_group1"]["enabled"];
                            $rulesGroup1LoginInfo = (empty($settingsData["rules_group1"]["login_info"])) ? "" : trim($settingsData["rules_group1"]["login_info"]);
                            $ddOptions = [
                                [
                                    "option" => "logged_in",
                                    "text"   => __( "logged in", "redirect-redirection" )
                                ],
                                [
                                    "option" => "not_logged_in",
                                    "text"   => __( "not logged in", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup1LoginInfo, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group1[enabled]" value="1" <?php checked($rulesGroup1Enabled === 1); ?>>
                                    <span><?php _e( "User who clicks on the link is​", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group1[login_info]", $ddOptions, $selected); ?>
                            </div>
                        </div>

                        <div class="rules-table__row">
                            <?php
                            $rulesGroup2Enabled = (empty($settingsData["rules_group2"]["enabled"])) ? 0 : (int) $settingsData["rules_group2"]["enabled"];
                            $rulesGroup2Role = (empty($settingsData["rules_group2"]["role"])) ? "" : trim($settingsData["rules_group2"]["role"]);
                            $ddOptions = [
                                [
                                    "option" => "has",
                                    "text"   => __( "has", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_have",
                                    "text" => __( "doesn't have", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup2Role, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group2[enabled]" value="1" <?php echo checked($rulesGroup2Enabled === 1); ?>>
                                    <span><?php _e( "User who clicks on the link", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group2[role]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 d-flex flex-col flex-1140-row align-items-start align-items-1140-center">
                                <span class="me-15"><?php _e( "WordPress user role​", "redirect-redirection" ); ?></span>
                                <?php
                                $isMultiple = true;
                                $roles = get_editable_roles();
                                $rulesGroup2RoleName = (empty($settingsData["rules_group2"]["role_name"])) ? "" : trim($settingsData["rules_group2"]["role_name"]);
                                $ddOptions = [];
                                $args = [
                                    "isMultiple" => $isMultiple,
                                ];
                                foreach ($roles as $role => $info) {
                                    $ddOptions[] = ["option" => $role, "text" => $info["name"]];
                                }
                                // TODO: convert selected to array in case of multiple is true
                                $rulesGroup2RoleNameArray = json_decode(stripslashes($rulesGroup2RoleName), ARRAY_A);
                                if ($rulesGroup2RoleNameArray !== null) {
                                    $selected = [];
                                    if (is_array($rulesGroup2RoleNameArray)) {
                                        foreach ($rulesGroup2RoleNameArray as $roleName) {
                                            $selected[] = ($found = array_search($roleName, array_column($ddOptions, "option"))) !== false ? $found : "false";
                                        }
                                    }
                                }else {
                                    $selected = ($found = array_search($rulesGroup2RoleName, array_column($ddOptions, "option"))) !== false ? $found : "false";
                                }
                                IRRPHelper::customDropdown("rules_group2[role_name]", $ddOptions, $selected, $args);
                                ?>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup3Enabled = (empty($settingsData["rules_group3"]["enabled"])) ? 0 : (int) $settingsData["rules_group3"]["enabled"];
                            $rulesGroup3Referrer = (empty($settingsData["rules_group3"]["referrer"])) ? "" : trim($settingsData["rules_group3"]["referrer"]);
                            $rulesGroup3ReferrerValue = (empty($settingsData["rules_group3"]["referrer_value"])) ? "" : trim($settingsData["rules_group3"]["referrer_value"]);
                            $rulesGroup3ReferrerRegex = (empty($settingsData["rules_group3"]["referrer_regex"])) ? 0 : (int) $settingsData["rules_group3"]["referrer_regex"];
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text"   => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text"   => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup3Referrer, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group3[enabled]" value="1" <?php echo checked($rulesGroup3Enabled === 1); ?>>
                                    <span><?php _e( "User’s referrer link​", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group3[referrer]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 d-flex flex-col flex-1140-row align-items-start align-items-1140-end flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label"><?php _e( "Referrer:", "redirect-redirection" ); ?></label>
                                    <input class="rules-table-input-group__input w-1140-270px" type="text" placeholder="https://www.example.com/blog/referrer-url​" name="rules_group3[referrer_value]" value="<?php esc_attr_e($rulesGroup3ReferrerValue); ?>">
                                </div>
                                <div class="mt-15 mt-1140-0 d-flex align-items-end justify-content-end flex-grow-1 pb-13">
                                    <span class="mr-15"><?php _e( "Regex:", "redirect-redirection" ); ?></span>
                                    <input class="rules-table__checkbox redi_nondef" type="checkbox" name="rules_group3[referrer_regex]" value="1" <?php echo checked($rulesGroup3ReferrerRegex === 1); ?>>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip">
                                            <?php _e( "Here you can set the user’s referrer URL (URL from which they are coming) that is used to determine the match, in addition to the source URL. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip cmib-tooltip--8">
                                            <?php _e( "Here you can set the user’s referrer URL (URL from which they are coming) that is used to determine the match, in addition to the source URL. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup4Enabled = (empty($settingsData["rules_group4"]["enabled"])) ? 0 : (int) $settingsData["rules_group4"]["enabled"];
                            $rulesGroup4Agent = (empty($settingsData["rules_group4"]["agent"])) ? "" : trim($settingsData["rules_group4"]["agent"]);
                            $rulesGroup4AgentValue = (empty($settingsData["rules_group4"]["agent_value"])) ? "" : trim($settingsData["rules_group4"]["agent_value"]);
                            $rulesGroup4AgentRegex = (empty($settingsData["rules_group4"]["agent_regex"])) ? 0 : (int) $settingsData["rules_group4"]["agent_regex"];
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text" => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text" => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup4Agent, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group4[enabled]" value="1" <?php echo checked($rulesGroup4Enabled === 1); ?>>
                                    <span><?php _e( "User’s agent​", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group4[agent]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 d-flex flex-col flex-1140-row align-items-start align-items-1140-end flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label"><?php _e( "User agent:​", "redirect-redirection" ); ?></label>
                                    <input class="rules-table-input-group__input w-1140-270px" type="text" placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.128 Safari/537.36" name="rules_group4[agent_value]" value="<?php esc_attr_e($rulesGroup4AgentValue); ?>">
                                </div>
                                <div class="mt-15 mt-1140-0 d-flex align-items-end justify-content-end flex-grow-1 pb-13">
                                    <span class="mr-15"><?php _e( "Regex:", "redirect-redirection" ); ?></span>
                                    <input class="rules-table__checkbox redi_nondef" type="checkbox" name="rules_group4[agent_regex]" value="1" <?php echo checked($rulesGroup4AgentRegex === 1); ?>>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip">
                                            <?php _e( "Here you can set the user’s browser agent in addition to the source URL to determine the match. It can use browser type, OS, (mobile) platform, etc. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip cmib-tooltip--8">
                                            <?php _e( "Here you can set the user’s browser agent in addition to the source URL to determine the match. It can use browser type, OS, (mobile) platform, etc. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup5Enabled = (empty($settingsData["rules_group5"]["enabled"])) ? 0 : (int) $settingsData["rules_group5"]["enabled"];
                            $rulesGroup5Cookie = (empty($settingsData["rules_group5"]["cookie"])) ? "" : trim($settingsData["rules_group5"]["cookie"]);
                            $rulesGroup5CookieName = (empty($settingsData["rules_group5"]["cookie_name"])) ? "" : trim($settingsData["rules_group5"]["cookie_name"]);
                            $rulesGroup5CookieValue = (empty($settingsData["rules_group5"]["cookie_value"])) ? "" : trim($settingsData["rules_group5"]["cookie_value"]);
                            $rulesGroup5CookieRegex = (empty($settingsData["rules_group5"]["cookie_regex"])) ? 0 : (int) $settingsData["rules_group5"]["cookie_regex"];
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text" => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text" => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup5Cookie, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group5[enabled]" value="1" <?php echo checked($rulesGroup5Enabled === 1); ?>>
                                    <span><?php _e( "User’s cookie​", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group5[cookie]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 d-flex flex-col flex-1140-row align-items-start align-items-1140-end flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group mr-1140-30">
                                    <label class="rules-table-input-group__label"><?php _e( "Cookie name:​", "redirect-redirection" ); ?></label>
                                    <span class="d-flex align-items-center">
                                        <input class="rules-table-input-group__input w-1140-130px" type="text" placeholder="namecookie" name="rules_group5[cookie_name]" value="<?php esc_attr_e($rulesGroup5CookieName); ?>">
                                    </span>
                                </div>
                                <div class="mt-15 mt-1140-0 rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label"><?php _e( "Cookie value:​", "redirect-redirection" ); ?></label>
                                    <input class="rules-table-input-group__input w-1140-130px" type="text" placeholder="valuecookie" name="rules_group5[cookie_value]" value="<?php esc_attr_e($rulesGroup5CookieValue); ?>">
                                </div>
                                <div class="mt-15 mt-1140-0 d-flex align-items-end justify-content-end flex-grow-1 pb-13">
                                    <span class="mr-15"><?php _e( "Regex:", "redirect-redirection" ); ?></span>
                                    <input class="rules-table__checkbox redi_nondef" type="checkbox" name="rules_group5[cookie_regex]" value="1" <?php echo checked($rulesGroup5CookieRegex === 1); ?>>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip">
                                            <?php _e( "Here you can set the cookie name and value in addition to the source URL to determine the match. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                    <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                        <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                        <p class="custom-modal-info-btn__tooltip cmib-tooltip--9">
                                            <?php _e( "Here you can set the cookie name and value in addition to the source URL to determine the match. If you want to apply RegEx, tick the checkbox.", "redirect-redirection" ); ?>
                                        </p>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup6Enabled = (empty($settingsData["rules_group6"]["enabled"])) ? 0 : (int) $settingsData["rules_group6"]["enabled"];
                            $rulesGroup6Ip = (empty($settingsData["rules_group6"]["ip"])) ? "" : trim($settingsData["rules_group6"]["ip"]);
                            $rulesGroup6IpValue = (empty($settingsData["rules_group6"]["ip_value"])) ? "" : trim($settingsData["rules_group6"]["ip_value"]);
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text"   => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text"   => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup6Ip, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group6[enabled]" value="1" <?php echo checked($rulesGroup6Enabled === 1); ?>>
                                    <span><?php _e( "User’s IP", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group6[ip]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label d-flex align-items-center">
                                        <span><?php _e( "IP:", "redirect-redirection" ); ?></span>
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--10">
                                                <?php _e( "Here you can set the user’s IP address that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </label>
                                    <span class="d-flex align-items-center">
                                        <input class="rules-table-input-group__input w-1140-400px" type="text" placeholder="178.243.58.24,184.545.65.23​" name="rules_group6[ip_value]" value="<?php esc_attr_e($rulesGroup6IpValue); ?>">
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--11">
                                                <?php _e( "Here you can set the user’s IP address that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup7Enabled = (empty($settingsData["rules_group7"]["enabled"])) ? 0 : (int) $settingsData["rules_group7"]["enabled"];
                            $rulesGroup7Server = (empty($settingsData["rules_group7"]["server"])) ? "" : trim($settingsData["rules_group7"]["server"]);
                            $rulesGroup7ServerValue = (empty($settingsData["rules_group7"]["server_value"])) ? "" : trim($settingsData["rules_group7"]["server_value"]);
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text"   => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text"   => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup7Server, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group7[enabled]" value="1" <?php echo checked($rulesGroup7Enabled === 1); ?>>
                                    <span><?php _e( "Server", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group7[server]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label d-flex align-items-center">
                                        <span><?php _e( "Server:", "redirect-redirection" ); ?></span>
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--12">
                                                <?php _e( "Here you can set the server name that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </label>
                                    <span class="d-flex align-items-center">
                                        <input class="rules-table-input-group__input w-1140-400px" type="text" placeholder="?​" name="rules_group7[server_value]" value="<?php esc_attr_e($rulesGroup7ServerValue); ?>">
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--13">
                                                <?php _e( "Here you can set the server name that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rules-table__row rules-table__row--align-end">
                            <?php
                            $rulesGroup8Enabled = (empty($settingsData["rules_group8"]["enabled"])) ? 0 : (int) $settingsData["rules_group8"]["enabled"];
                            $rulesGroup8Language = (empty($settingsData["rules_group8"]["language"])) ? "" : trim($settingsData["rules_group8"]["language"]);
                            $rulesGroup8LanguageValue = (empty($settingsData["rules_group8"]["language_value"])) ? "" : trim($settingsData["rules_group8"]["language_value"]);
                            $ddOptions = [
                                [
                                    "option" => "matches",
                                    "text"   => __( "matches", "redirect-redirection" )
                                ],
                                [
                                    "option" => "does_not_match",
                                    "text"   => __( "doesn't match", "redirect-redirection" )
                                ],
                            ];
                            $selected = ($found = array_search($rulesGroup8Language, array_column($ddOptions, "option"))) !== false ? $found : "false";
                            ?>
                            <div class="rules-table__col rules-table__col--1">
                                <span class="rules-table__row-label rules-table__row-label--flex">
                                    <input class="rules-table__checkbox rules-table__checkbox--1" type="checkbox" name="rules_group8[enabled]" value="1" <?php echo checked($rulesGroup8Enabled === 1); ?>>
                                    <span><?php _e( "Language", "redirect-redirection" ); ?></span>
                                </span>
                            </div>
                            <div class="rules-table__col rules-table__col--2">
                                <?php IRRPHelper::customDropdown("rules_group8[language]", $ddOptions, $selected); ?>
                            </div>
                            <div class="rules-table__col rules-table__col--3 flex-grow-1">
                                <div class="rules-table__input-group rules-table-input-group">
                                    <label class="rules-table-input-group__label d-flex align-items-center">
                                        <span><?php _e( "Language:", "redirect-redirection" ); ?></span>
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--small-devices">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--14">
                                                <?php _e( "Here you can set the browser language that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </label>
                                    <span class="d-flex align-items-center">
                                        <input class="rules-table-input-group__input w-1140-400px" type="text" placeholder="en-GB,en,fr-FR,fr," name="rules_group8[language_value]" value="<?php esc_attr_e($rulesGroup8LanguageValue); ?>">
                                        <span role="button" tabindex="1" class="custom-modal__info-btn custom-modal__info-btn--large-devices custom-modal-info-btn">
                                            <img src="<?php echo plugins_url(IRRP_DIR_NAME . "/assets/css/assets/images/info-icon.svg"); ?>" alt="<?php _e( "info icon", "redirect-redirection" ); ?>">
                                            <p class="custom-modal-info-btn__tooltip cmib-tooltip--15">
                                                <?php _e( "Here you can set the browser language that is used to determine the match, in addition to the source URL​", "redirect-redirection" ); ?>
                                            </p>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-box__footer settings-box-footer">
                    <input type="hidden" value="" class="ir-redirect-settings"/>
                    <p></p>
                    <p class="settings-box-footer__paragraph" style="display: none;"><?php
                        printf(
                            __( 'You can change your default settings on the %1$sOptions tab​%2$s​', 'redirect-redirection' ),
                            '<a class="settings-box__link" href="#!">',
                            '</a>'
                        );
                    ?></p>
                    <button class="settings-box-footer__close-btn ir-default-settings-close cta__cancel-btn ir-header-cancel" type="button">
                        <?php _e( "Cancel changes and Close", "redirect-redirection" ); ?>
                    </button>
                    <p class="hidden">
                        <button class="settings-box-footer__btn ir-default-settings-close ir-default-settings-save">
                            <?php
                            _e( "Save Option Changes", "redirect-redirection" );
                            ?>
                        </button>
                    </p>
                </div>
            </form>
            <textarea class="ir-hidden ir-default-settings" name="ir-default-settings"></textarea>
        </div>
    </div>
</div>
