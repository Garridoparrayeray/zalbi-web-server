<?php

if (!defined("ABSPATH")) {
    exit();
}

// class for specific redirects ajax functions
class IRRPHelperAjax implements IRRPConstants {

    /**
     * @var IRRPDBManager
     */
    private $dbManager;

    /**
     * @var IRRPSettings
     */
    private $settings;

    /**
     * @var IRRPHelper
     */
    private $helper;

    public function __construct($dbManager, $settings, $helper) {
        $this->dbManager = $dbManager;
        $this->settings = $settings;
        $this->helper = $helper;

        // AJAX ACTIONS
        add_action("wp_ajax_irAddRedirect", [&$this, "addRedirect"]);
        add_action("wp_ajax_irInstantEditRedirect", [&$this, "instantEditRedirect"]);
        add_action("wp_ajax_irLoadRedirectSettings", [&$this, "loadRedirectSettings"]);
        add_action("wp_ajax_irSaveRedirectSettings", [&$this, "saveRedirectSettings"]);
        add_action("wp_ajax_irDeleteRedirect", [&$this, "deleteRedirect"]);
        add_action("wp_ajax_irStatusBulkEdit", [&$this, "statusBulkEdit"]);
        add_action("wp_ajax_irBulkDelete", [&$this, "bulkDelete"]);
        add_action("wp_ajax_irRedirectionPageContent", [&$this, "redirectionPageContent"]);
        add_action("wp_ajax_irLiveSearch", [&$this, "liveSearch"]);
        add_action("wp_ajax_irSelectAll", [&$this, "selectAll"]);
        // LOGS
        add_action("wp_ajax_irLogPageContent", [&$this, "logPageContent"]);
        add_action("wp_ajax_irLogFilter", [&$this, "logFilter"]);
        add_action("wp_ajax_irCronLogDeleteOption", [&$this, "cronLogDeleteOption"]);
        add_action("wp_ajax_irLogStatusChange", [&$this, "logStatusChange"]);

        // REDIRECT RULES
        add_action("wp_ajax_irAddRedirectRule", [&$this, "addRedirectRule"]);
    }

    // AJAX FUNCTIONS //

    /**
     * new redirect
     */
    public function addRedirect() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];
        $from = empty($_POST["from"]) ? "" : IRRPHelper::removeDoubleSlashes(trim(sanitize_text_field(urldecode($_POST["from"]))));
        $to = empty($_POST["to"]) ? "" : IRRPHelper::removeDoubleSlashes(trim(sanitize_text_field(urldecode($_POST["to"]))));
        $status = empty($_POST["status"]) ? 1 : (int) $_POST["status"];
        $timestamp = current_time("timestamp");
        $redirectionType = self::TYPE_REDIRECTION;
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(stripslashes($_POST["data"]), ARRAY_A));
        $data = array_filter($data, function($value) {
            return $value !== "";
        });
        $settings = array_replace_recursive($this->settings->getDefaultSettings(), $data);

        if (!is_array($selected)) {
            $selected = [];
        }

        if ($from && $to) {
            $disallowedSymbols = '#[\<\>\"\'\{\}\[\]\|\\,~\^`;@\$\!\*\(\)]+#isu';
            if (preg_match($disallowedSymbols, rawurlencode($from)) || preg_match($disallowedSymbols, rawurlencode($to))) {
                $response["status"] = "error";
                $response["message"] = __("Please ensure your entry is valid!", "redirect-redirection");
                wp_send_json_error($response);
            }

            $from = ($from[0] == '/') ? home_url() . $from : $from;
            $to = ($to[0] == '/') ? home_url() . $to : $to;
        
            $from = $this->prependProtocolIfNeeded($from);
            $to = $this->prependProtocolIfNeeded($to);
        
            // If 'to' is an external URL, ensure it uses HTTPS
            $toHost = parse_url($to, PHP_URL_HOST);
            if ($toHost && $toHost !== parse_url(home_url(), PHP_URL_HOST)) {
                $to = preg_replace('#^http://#', 'https://', $to);
            }

            $redirect = $this->dbManager->get($id);
            $urlData = parse_url($from);
            $match = empty($urlData["path"]) ? "/" : trim($urlData["path"]);

            if ($redirect) { // redirect already exists, means we should edit it
                $data = ["from" => $from, "match" => $match, "to" => $to];
                $dataFormat = ["%s", "%s", "%s"];

                $isUpdated = $this->dbManager->edit($id, $data, $dataFormat);
                if ($isUpdated) {
                    $response["status"] = "success";
                    $response["message"] = __("Redirection edited successfully", "redirect-redirection");

                    $args = ["type" => $redirectionType];
                    $countRedirects = (int) $this->dbManager->getCount($args);
                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;
                    ob_start();
                    include_once "settings/layouts/common/default-settings-modal.php";
                    $response["form"] = ob_get_clean();

                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, cannot update a db row", "redirect-redirection");
                    wp_send_json_error($response);
                }
            } else { // not found, adding...
                $args = ["type" => $redirectionType];
                $redirect = [
                    "from" => $from,
                    "match" => $match,
                    "to" => $to,
                    "status" => $status,
                    "timestamp" => $timestamp,
                    "type" => $redirectionType,
                ];

                $insertId = $this->dbManager->add($redirect);
                if ($insertId) {
                    $response["status"] = "success";
                    $response["message"] = __("Redirection added successfully", "redirect-redirection");

                    // adding redirect metadata
                    foreach ($settings as $key => $value) {
                        $metaKey = esc_sql($key);
                        if (is_array($value)) {
                            $metaValue = maybe_serialize(array_map("esc_sql", $value));
                        } else {
                            $metaValue = $value ? esc_sql($value) : "";
                        }

                        $data = [
                            "redirect_id" => $insertId,
                            "meta_key" => $metaKey,
                            "meta_value" => $metaValue,
                        ];

                        if ($metaKey) {
                            $this->dbManager->addMeta($data);
                        }
                    }

                    $countRedirects = (int) $this->dbManager->getCount($args);
                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $currentOffset = 0;

                    // buidling pagination
                    ob_start();
                    $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
                    $response["pagination"] = ob_get_clean();

                    $args = [
                        "offset" => $currentOffset * self::PER_PAGE_REDIRECTIONS,
                        "where" => [
                            "condition" => "AND",
                            "clauses" => [
                                ["column" => "type", "value" => $redirectionType, "compare" => "="]
                            ],
                        ]
                    ];

                    $redirects = $this->dbManager->getAll($args);

                    $response["content"] = $this->helper->buildRedirectsHtml($redirects, $selected);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;
                    ob_start();
                    include_once "settings/layouts/common/default-settings-modal.php";
                    $response["form"] = ob_get_clean();

                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
                    wp_send_json_error($response);
                }
            }
        } else {
            $response["status"] = "error";
            if (!$from || !$to) {
                $response["message"] = __("Please ensure your entry is valid!", "redirect-redirection");
            } else {
                $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            }
            wp_send_json_error($response);
        }
    }

    /**
     * instant edit a redirect
     */
    public function instantEditRedirect() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(wp_unslash($_POST["data"])));

        if ($id && $data && is_array($data)) {
            $redirect = $this->dbManager->get($id);
            if ($redirect) { // redirect found, editing...
                $updateData = [];
                $dataFormat = [];
                $rules = $this->dbManager->getRules();
                $are404sId = $this->dbManager->isAre404sRuleExists($rules);
                $allUrlsId = $this->dbManager->isAllURLsRuleExists($rules);
                foreach ($data as $d) {
                    $item = (array) $d;
                    $column = wp_unslash($item["column"]);
                    $value = wp_unslash($item["value"]);

                    if (!$column) {
                        $response["status"] = "error";
                        $response["message"] = __("Something went wrong, cannot update a db row", "redirect-redirection");
                        wp_send_json_error($response);
                    }

                    if ($are404sId && $this->dbManager->isRuleType("are-404s", $id) && $value) {
                        $response["status"] = "error";
                        $response["code"] = "404_exists";
                        $response["message"] = __("The 'Are 404s' redirection rule is already enabled.", "redirect-redirection");
                        wp_send_json_error($response);
                    }

	                if ($allUrlsId && $this->dbManager->isRuleType("all-urls", $id) && $value) {
		                $response["status"] = "error";
		                $response["code"] = "all_urls_exists";
		                $response["message"] = __("The 'All URLs' redirection rule is already enabled.", "redirect-redirection");
		                wp_send_json_error($response);
	                }

                    $dataFormat[] = ($column === "status") ? "%d" : "%s";
                    $updateData[$column] = $value;
                    if ($column === "from") {
                        $urlData = parse_url($value);
                        $match = empty($urlData["path"]) ? "/" : trim($urlData["path"]);
                        $updateData["match"] = $match;
                        $dataFormat[] = "%s";
                    }
                }

                $isUpdated = $this->dbManager->edit($id, $updateData, $dataFormat);
                if ($isUpdated) {
                    $response["status"] = "success";
                    $response["message"] = __("Redirection edited successfully", "redirect-redirection");
                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, cannot update a db row", "redirect-redirection");
                    wp_send_json_error($response);
                }
            } else { // not found, send an error message
                $response["status"] = "error";
                $response["message"] = __("Redirection not exists", "redirect-redirection");
                wp_send_json_error($response);
            }
        } else {
            $response["status"] = "error";
            if (!$id) {
                $response["message"] = __("Redirect not found by given ID!", "redirect-redirection");
            } else if (!$column) {
                $response["message"] = __("Database error, unknown table column!", "redirect-redirection");
            } else if (!$value) {
                $response["message"] = __("New value cannot be empty!", "redirect-redirection");
            } else {
                $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            }
            wp_send_json_error($response);
        }
    }

    /**
     * load the redirect settings
     */
    public function loadRedirectSettings() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];

        if ($id) {
            $redirect = $this->dbManager->get($id);
            if ($redirect) { // redirect found, loading...
                $loadedData = $this->dbManager->getMeta($id);

                $settingsData = array_replace_recursive($this->settings->getDefaultSettings(), $loadedData);
                $response["status"] = "success";
                $response["message"] = __("Redirection settings loaded", "redirect-redirection");
                ob_start();
                include_once "settings/layouts/common/default-settings-modal.php";
                $response["content"] = ob_get_clean();

                // $response["options"] = $settingsData;
                $response["from"] = $redirect["from"];
                $response["to"] = $redirect["to"];
                $response["type"] = $redirect["type"];
                if ($redirect["type"] === self::TYPE_REDIRECTION_RULE) {
                    $response["criterias"] = $loadedData[self::META_KEY_CRITERIAS];
                    $response["action"] = $loadedData[self::META_KEY_ACTION];
                }

                wp_send_json_success($response);
            } else { // not found, send an error message
                $response["status"] = "error";
                $response["message"] = __("Redirection not exists", "redirect-redirection");
                wp_send_json_error($response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = __("Redirect ID must be positive number!", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    /**
     * save redirect settings
     */
    public function saveRedirectSettings() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(stripslashes($_POST["data"]), ARRAY_A));
        $data = IRRPHelper::unescapeData($data);
        $parsed = array_replace_recursive($this->settings->getDefaultSettings(), $data);

        if ($id && $parsed) {
            $redirect = $this->dbManager->get($id);

            if ($redirect) { // redirect found, editing...                
                $response["status"] = "success";
                $response["message"] = __("Redirect settings updated", "redirect-redirection");

                // updating redirect metadata            
                foreach ($parsed as $key => $value) {
                    $metaKey = esc_sql($key);
                    if (is_array($value)) {
                        $metaValue = maybe_serialize(array_map("esc_sql", $value));
                    } else {
                        $metaValue = $value ? esc_sql($value) : "";
                    }

                    $parsedData = ["meta_value" => $metaValue];
                    $this->dbManager->updateMeta($id, $metaKey, $parsedData);
                }
                $response["type"] = (int) $this->dbManager->getMeta($id, "redirect_code");

                wp_send_json_success($response);
            } else { // not found, send an error message
                $response["status"] = "error";
                $response["message"] = __("Redirection not exists", "redirect-redirection");
                wp_send_json_error($response);
            }
        } else {
            $response["status"] = "error";
            if (!$id) {
                $response["message"] = __("Redirection ID must be INTEGER > 0!", "redirect-redirection");
            } else {
                $response["message"] = __("Redirect settings cannot be empty!", "redirect-redirection");
            }
            wp_send_json_error($response);
        }
    }

    /**
     * deletes a redirect
     */
    public function deleteRedirect() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];
        $currentOffset = empty($_POST["currentOffset"]) ? 0 : (int) $_POST["currentOffset"];
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));

        if (!is_array($selected)) {
            $selected = [];
        }

        if ($id && $currentOffset >= 0) {
            $redirect = $this->dbManager->get($id);
            if (empty($redirect)) { // not found, send an error message
                $response["status"] = "error";
                $response["message"] = __("Redirection not exists", "redirect-redirection");
                wp_send_json_error($response);
            } else { // redirect found, deleting...
                if ($this->dbManager->delete($id)) {
                    $response["status"] = "success";
                    $response["message"] = __("Redirection deleted successfully", "redirect-redirection");

                    $this->dbManager->deleteMeta($id); // check redirection type before delete -- IMPORTANT for rules

                    $args = ["type" => $redirectionType];
                    $countRedirects = (int) $this->dbManager->getCount($args);
                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $currentOffset = (($currentOffset + 1) > $countPages) ? $currentOffset - 1 : $currentOffset;

                    if ($currentOffset < 0) {
                        $currentOffset = 0;
                    }

                    // buidling pagination
                    ob_start();
                    $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
                    $response["pagination"] = ob_get_clean();

                    $args = [
                        "offset" => $currentOffset * self::PER_PAGE_REDIRECTIONS,
                        "where" => [
                            "condition" => "AND",
                            "clauses" => [
                                ["column" => "type", "value" => $redirectionType, "compare" => "="]
                            ],
                        ]
                    ];
                    $redirects = $this->dbManager->getAll($args);

                    $response["content"] = $this->helper->buildRedirectsHtml($redirects, $selected);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;

                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
                    wp_send_json_error($response);
                }
            }
        } else {
            $response["status"] = "error";
            if (!$id) {
                $response["message"] = __("Redirection ID must be INTEGER > 0!", "redirect-redirection");
            } else if (!$currentOffset) {
                $response["message"] = __("Current page must be INTEGER > 0!", "redirect-redirection");
            } else {
                $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            }
            wp_send_json_error($response);
        }
    }

    /**
     * redirect status bulk edit
     */
    public function statusBulkEdit() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));
        $status = isset($_POST["status"]) ? (int) $_POST["status"] : "";

        $selected = $this->validateSelectedBulks($selected, $status);
        if($selected === false){
            $response["status"] = "error";
            $response["message"] = __("You cannot edit the selected redirects", "redirect-redirection");
            wp_send_json_error($response);
        }

        if (!is_array($selected)) {
            $selected = [];
        }

        if ($status >= 0) {

            $editedCount = (int) $this->dbManager->statusBulkEdit($selected, $status);
            if ($editedCount >= 1) {
                $response["status"] = "success";
                if ($editedCount > 1) {
                    $response["message"] = $editedCount . " " . __("redirects have been edited", "redirect-redirection");
                } else {
                    $response["message"] = $editedCount . " " . __("redirect has been edited", "redirect-redirection");
                }
                wp_send_json_success($response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = __("Redirect status cannot be empty", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    /**
     * validate selected bulks
     * @param array $selected - selected redirects ids
     * @return array|bool - false if validation fails
     */
    private function validateSelectedBulks(array $selected, $status) {
        if (!is_array($selected)) {
            $selected = [];
        }

        if (empty($selected)) {
            return false;
        }
        // check if there is active are-404s||all-urls rule
        $rules = $this->dbManager->getRules();
        $isAre404s = $this->dbManager->isAre404sRuleExists($rules);
        $isAllUrls = $this->dbManager->isAllURLsRuleExists($rules);

        if ( $status === 0 ){
            return $selected;
        }
        $are404sCount = 0;
        $allUrlsCount = 0;
        foreach($selected as $id){
            $ruleIsAre404s = $this->dbManager->isRuleType("are-404s",$id);
            $ruleIsAllUrls = $this->dbManager->isRuleType("all-urls",$id);

            if($ruleIsAre404s){
                if($isAre404s || $are404sCount){
                    return false;
                }
                $are404sCount++;
            }
            if($ruleIsAllUrls){
                if($isAllUrls || $allUrlsCount){
                    return false;
                }
                $allUrlsCount++;
            }
        }
        return $selected;
    }

    /**
     * redirects bulk delete
     */
    public function bulkDelete() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $status = isset($_POST["status"]) ? (int) $_POST["status"] : "";
        $search = empty($_POST["search"]) ? "" : trim(sanitize_text_field($_POST["search"]));
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));

        if (!is_array($selected)) {
            $selected = [];
        }

        if ($status === -1) {
            $deletedCount = (int) $this->dbManager->bulkDelete($selected);
            if ($deletedCount) {

                if ($search) {
                    $args = ["type" => $redirectionType];

                    $redirects = $this->dbManager->search($search, $args);
                    $countRedirects = $this->dbManager->searchCount($search, $args);
                } else {
                    $args = [
                        "where" => [
                            "condition" => "AND",
                            "clauses" => [
                                ["column" => "type", "value" => $redirectionType, "compare" => "="]
                            ],
                        ]
                    ];

                    $redirects = $this->dbManager->getAll($args);

                    $args = ["type" => $redirectionType];
                    $countRedirects = $this->dbManager->getCount($args);
                }

                if (!empty($redirects) && is_array($redirects)) {

                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $currentOffset = 0;

                    // buidling pagination
                    ob_start();
                    $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
                    $response["pagination"] = ob_get_clean();

                    $response["content"] = $this->helper->buildRedirectsHtml($redirects, $selected);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;
                } else {
                    $response["pagination"] = "";
                    $response["content"] = "";
                    $response["countPages"] = 0;
                    $response["countRedirects"] = 0;
                }

                $response["status"] = "success";
                if ($deletedCount > 1) {
                    $response["message"] = $deletedCount . " " . __("redirects have been deleted", "redirect-redirection");
                } else {
                    $response["message"] = $deletedCount . " " . __("redirect has been deleted", "redirect-redirection");
                }
                wp_send_json_success($response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    /**
     * redirection page content
     */
    public function redirectionPageContent() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $offset = empty($_POST["offset"]) ? 0 : absint($_POST["offset"]);
        $search = empty($_POST["search"]) ? "" : trim(sanitize_text_field($_POST["search"]));
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));

        if (!is_array($selected)) {
            $selected = [];
        }

        $args = ["offset" => $offset * self::PER_PAGE_REDIRECTIONS, "type" => $redirectionType];
        if ($search) {
            $redirects = $this->dbManager->search($search, $args);
            $countRedirects = $this->dbManager->searchCount($search, $args);
        } else {
            $args["where"] = [
                "condition" => "AND",
                "clauses" => [
                    ["column" => "type", "value" => $redirectionType, "compare" => "="]
                ],
            ];

            $redirects = $this->dbManager->getAll($args);
            $countRedirects = $this->dbManager->getCount($args);
        }

        if (!empty($redirects) && is_array($redirects)) {

            $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
            $currentOffset = 0;

            // buidling pagination
            ob_start();
            $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
            $response["pagination"] = ob_get_clean();

            $response["content"] = $this->helper->buildRedirectsHtml($redirects, $selected);
            $page = $offset + 1;
            $response["status"] = "success";
            $response["message"] = sprintf(__("Showing page %d data.", "redirect-redirection"), $page);
            wp_send_json_success($response);
        } else {
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    public function liveSearch() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $search = isset($_POST["search"]) ? trim(sanitize_text_field($_POST["search"])) : "";
        $searchIsNotEmpty = strlen($search);
        $showAll = empty($_POST["showAll"]) ? 0 : (int) $_POST["showAll"];
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));

        if ($searchIsNotEmpty || $showAll) {
            $args = ["type" => $redirectionType];
            if ($searchIsNotEmpty) {
                $redirects = $this->dbManager->search($search, $args);
                $countRedirects = $this->dbManager->searchCount($search, $args);
            } else {
                $args["where"] = [
                    "condition" => "AND",
                    "clauses" => [
                        ["column" => "type", "value" => $redirectionType, "compare" => "="]
                    ],
                ];
                $redirects = $this->dbManager->getAll($args);
                $countRedirects = $this->dbManager->getCount($args);
            }

            if (!empty($redirects) && is_array($redirects)) {
                $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                $currentOffset = 0;

                // buidling pagination
                ob_start();
                $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
                $response["pagination"] = ob_get_clean();

                $response["content"] = $this->helper->buildRedirectsHtml($redirects);
                $response["status"] = "success";
                $response["message"] = __("Showing search results", "redirect-redirection");
                wp_send_json_success($response);
            } else {
                $response["status"] = "error";
                $response["message"] = __("No redirects found", "redirect-redirection");
                $response["content"] = "";
                $response["pagination"] = "";
                wp_send_json_success($response);
            }
        } else {
            $response["status"] = "error";
            $response["message"] = __("Search text cannot be empty!", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    public function selectAll() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $search = empty($_POST["search"]) ? "" : trim(sanitize_text_field($_POST["search"]));
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", json_decode(stripslashes(trim($_POST["selected"]))));

        $args = ["fields" => ["id"], "limit" => null, "type" => $redirectionType];

        if ($search) {
            $redirects = $this->dbManager->search($search, $args);
            $countRedirects = $this->dbManager->searchCount($search, $args);
        } else {
            $args["where"] = [
                "condition" => "AND",
                "clauses" => [
                    ["column" => "type", "value" => $redirectionType, "compare" => "="]
                ],
            ];
            $redirects = $this->dbManager->getAll($args);
            $countRedirects = $this->dbManager->getCount($args);
        }

        if (!empty($redirects) && is_array($redirects)) {
            $unchecked = array_diff($redirects, $selected);
            if ($unchecked) {
                $response["status"] = "success";
                $response["message"] = sprintf(__("All %d redirects selected", "redirect-redirection"), count($redirects));
                $response["selected"] = json_encode($redirects);
            } else {
                $response["status"] = "success";
                $response["message"] = sprintf(__("All %d redirects deselected", "redirect-redirection"), count($redirects));
                $response["selected"] = json_encode($unchecked);
            }
            wp_send_json_success($response);
        } else {
            $response["status"] = "error";
            $response["message"] = __("No redirects found", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    //***************************************************************************/
    //************************ REDIRECTION RULES --START ************************/
    //***************************************************************************/

    /**
     * new redirection rule
     */
    public function addRedirectRule() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $id = empty($_POST["id"]) ? 0 : (int) $_POST["id"];
        $rules = empty($_POST["rules"]) ? [] : IRRPHelper::sanitizeData(json_decode(IRRPHelper::removeDoubleSlashes( stripslashes( ($_POST["rules"]) ) ), true));
        $status = empty($_POST["status"]) ? 1 : (int) $_POST["status"];
        $timestamp = current_time("timestamp");
        $redirectionType = self::TYPE_REDIRECTION_RULE;
        $selected = empty($_POST["selected"]) ? [] : array_map("intval", (json_decode(stripslashes(trim($_POST["selected"])))));
        $data = empty($_POST["data"]) ? [] : IRRPHelper::sanitizeData(json_decode(stripslashes($_POST["data"]), ARRAY_A));
        $settings = array_replace_recursive($this->settings->getDefaultSettings(), $data);

        if (!is_array($selected)) {
            $selected = [];
        }
        if (!empty($rules["criterias"]) && !empty($rules["action"]) && is_array($rules)) {
            // found return the index of the element in $rules[self::META_KEY_CRITERIAS] (return 0 if found)
            // so make error in line 786 && 790.
            // $isAre404s = ($found = array_search("are-404s", array_column($rules[self::META_KEY_CRITERIAS], "criteria"))) === false ? false : $found; 
            // $isAllUrls = ($found = array_search("all-urls", array_column($rules[self::META_KEY_CRITERIAS], "criteria"))) === false ? false : $found;
            $isAre404s = (array_search("are-404s", array_column($rules[self::META_KEY_CRITERIAS], "criteria"))) === false ? false : true;
            $isAllUrls = (array_search("all-urls", array_column($rules[self::META_KEY_CRITERIAS], "criteria"))) === false ? false : true;

            if (($isAre404s !== false || $isAllUrls !== false) && !$id) {
            	$rulesInDb = $this->dbManager->getRules();

                if ($this->dbManager->isAre404sRuleExists($rulesInDb) && $isAre404s) {
                    $response["status"] = "error";
                    $response["message"] = __("The 'Are 404s' redirection rule is already enabled.", "redirect-redirection");
                    wp_send_json_error($response);
                } else if ($this->dbManager->isAllURLsRuleExists($rulesInDb) && $isAllUrls) {
	                $response["status"] = "error";
	                $response["message"] = __("The 'All URLs' redirection rule is already enabled.", "redirect-redirection");
	                wp_send_json_error($response);
                }
            }

            $redirect = $this->dbManager->get($id);

            if ($redirect) { // redirect already exists, means we should edit it
                $args = ["type" => $redirectionType];
                $from = "";
                $match = "";
                $to = empty($rules["action"]["value"]) ? "" : $rules["action"]["value"];

                $data = ["from" => $from, "match" => $match, "to" => $to];
                $dataFormat = ["%s", "%s", "%s"];

                $isUpdated = $this->dbManager->edit($id, $data, $dataFormat);
                if ($isUpdated) {
                    $redirect = $this->dbManager->get($id);
                    // updating redirect metadata >> criterias,action
                    $criteriaData = ["meta_value" => maybe_serialize($rules["criterias"])];
                    $this->dbManager->updateMeta($id, self::META_KEY_CRITERIAS, $criteriaData);

                    $actionData = ["meta_value" => maybe_serialize($rules["action"])];
                    $this->dbManager->updateMeta($id, self::META_KEY_ACTION, $actionData);

                    $response["status"] = "success";
                    $response["message"] = __("Redirection rule edited successfully", "redirect-redirection");

                    $args = ["type" => $redirectionType];
                    $countRedirects = (int) $this->dbManager->getCount($args);
                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;
                    $response["html"] = $this->helper->buildRedirectsHtml([$redirect], $selected);
                    ob_start();
                    include_once "settings/layouts/common/default-settings-modal.php";
                    $response["form"] = ob_get_clean();

                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, cannot update a db row", "redirect-redirection");
                    wp_send_json_error($response);
                }
            } else { // not found, adding...
                if (IRRPHelper::isEmpty($rules) && $rules['action']['name'] != 'urls-with-removed-string') {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, please ensure the entry is valid!", "redirect-redirection");
                    wp_send_json_error($response);
                }

                $args = ["type" => $redirectionType];

                $from = "";
                $match = "";
                $to = empty($rules["action"]["value"]) ? "" : $rules["action"]["value"];
                $redirect = [
                    "from" => $from,
                    "match" => $match,
                    "to" => $to,
                    "status" => $status,
                    "timestamp" => $timestamp,
                    "type" => $redirectionType,
                ];

                $insertId = $this->dbManager->add($redirect);

                if ($insertId) {
                    // adding redirect metadata >> settings
                    //foreach ($this->settings->getData() as $key => $value) {
                    foreach ($settings as $key => $value) {
                        $metaKey = esc_sql($key);
                        if (is_array($value)) {
                            $metaValue = maybe_serialize(array_map("esc_sql", $value));
                        } else {
                            $metaValue = $value ? esc_sql($value) : "";
                        }

                        $data = ["redirect_id" => $insertId, "meta_key" => $metaKey, "meta_value" => $metaValue];

                        if ($metaKey && $insertId) {
                            $this->dbManager->addMeta($data);
                        }
                    }

                    // adding redirect metadata >> criterias,action
                    $criteriaData = ["redirect_id" => $insertId, "meta_key" => self::META_KEY_CRITERIAS, "meta_value" => maybe_serialize($rules["criterias"])];
                    $this->dbManager->addMeta($criteriaData);

                    $actionData = ["redirect_id" => $insertId, "meta_key" => self::META_KEY_ACTION, "meta_value" => maybe_serialize($rules["action"])];
                    $this->dbManager->addMeta($actionData);

                    $countRedirects = (int) $this->dbManager->getCount($args);
                    $countPages = ceil($countRedirects / self::PER_PAGE_REDIRECTIONS);
                    $currentOffset = 0;

                    // buidling pagination
                    ob_start();
                    $this->helper->buildPaginationHtml($countRedirects, $countPages, $currentOffset);
                    $response["pagination"] = ob_get_clean();

                    $args = [
                        "offset" => $currentOffset * self::PER_PAGE_REDIRECTIONS,
                        "where" => [
                            "condition" => "AND",
                            "clauses" => [
                                ["column" => "type", "value" => $redirectionType, "compare" => "="]
                            ],
                        ]
                    ];

                    $redirects = $this->dbManager->getAll($args);

                    $response["status"] = "success";
                    $response["message"] = __("Redirection rule added successfully", "redirect-redirection");

                    $response["content"] = $this->helper->buildRedirectsHtml($redirects, $selected);
                    $response["countPages"] = $countPages;
                    $response["countRedirects"] = $countRedirects;
                    ob_start();
                    include_once "settings/layouts/common/default-settings-modal.php";
                    $response["form"] = ob_get_clean();

                    wp_send_json_success($response);
                } else {
                    $response["status"] = "error";
                    $response["message"] = __("Something went wrong, cannot add redirection rule!", "redirect-redirection");
                    wp_send_json_error($response);
                }
            }
        } else {
            $response["status"] = "error";
            $response["message"] = __("Redirection rule data cannot be empty!", "redirect-redirection");
            wp_send_json_error($response);
        }
    }

    //***************************************************************************/
    //************************ REDIRECTION RULES --END **************************/
    //***************************************************************************/
    //***************************************************************************/
    //************************ REDIRECTION LOGS --START *************************/
    //***************************************************************************/

    /**
     * log page content
     */
    public function logPageContent() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $offset = ($_offset = filter_input(INPUT_POST, "offset", FILTER_SANITIZE_NUMBER_INT)) ? $_offset : 0;
        $logType = trim(filter_input(INPUT_POST, "log_type", FILTER_SANITIZE_STRING));

        $perPage = $this->helper->getItemsPerPage("log");
        $args = ["offset" => $offset * $perPage];

        if ($logType === "404s") {
            $args["response_code"] = 404;
        }

        $logs = $this->dbManager->logGet($args);

        if (empty($logs) || !is_array($logs)) {
            $response["status"] = "error";
            $response["message"] = __("Something went wrong, please try again", "redirect-redirection");
            wp_send_json_error($response);
        }

        $args["count"] = true;
        $args["offset"] = 0;
        $countLogs = $this->dbManager->logGet($args);

        $countPages = ceil($countLogs / $perPage);

        // buidling pagination
        ob_start();
        $this->helper->buildPaginationHtml($countLogs, $countPages, 0, "log");
        $response["pagination"] = ob_get_clean();

        $response["content"] = $this->helper->buildLogsHtml($logs);
        $page = $offset + 1;
        $response["status"] = "success";
        $response["message"] = sprintf(__("Showing page %d data.", "redirect-redirection"), $page);
        wp_send_json_success($response);
    }

    public function logFilter() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        $response = ["status" => "", "message" => ""];
        $logType = trim(filter_input(INPUT_POST, "log_type", FILTER_SANITIZE_STRING));

        $args = ["offset" => 0];

        if ($logType === "404s") {
            $args["response_code"] = 404;
        }

        $logs = $this->dbManager->logGet($args);

        if (empty($logs) || !is_array($logs)) {
            $response["status"] = "success";
            $response["message"] = esc_html__("Nothing to show", "redirect-redirection");
            $response["content"] = "";
            $response["pagination"] = "";
            wp_send_json_success($response);
        }

        $args["count"] = true;
        $countLogs = $this->dbManager->logGet($args);
        $perPage = $this->helper->getItemsPerPage("log");

        $countPages = ceil($countLogs / $perPage);

        // buidling pagination
        ob_start();
        $this->helper->buildPaginationHtml($countLogs, $countPages, 0, "log");
        $response["pagination"] = ob_get_clean();

        $response["content"] = $this->helper->buildLogsHtml($logs);
        $page = 1; // showing first page after aplpying filter
        $response["status"] = "success";
        $response["message"] = sprintf(__("Showing page %d data.", "redirect-redirection"), $page);
        wp_send_json_success($response);
    }

    public function cronLogDeleteOption() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );
        
        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $response = ["status" => "", "message" => ""];
        $cronLogDelete = strtolower(trim(filter_input(INPUT_POST, "cron_log_delete_option", FILTER_SANITIZE_STRING)));

        if (empty($cronLogDelete)) {
            $response["status"] = "error";
            $response["message"] = esc_html__("The auto delete value cannot be empty", "redirect-redirection");
            wp_send_json_error($response);
        }
        switch ($cronLogDelete) {
            case "never":
                $cronLogDeleteOptionId = 0;
                break;
            case "older-than-a-week":
                $cronLogDeleteOptionId = 1;
                break;
            case "older-than-a-month":
                $cronLogDeleteOptionId = 2;
                break;
            default:
                $response["status"] = "error";
                $response["message"] = esc_html__("The auto delete value is not valid", "redirect-redirection");
                wp_send_json_error($response);
        }

        update_option(self::OPTIONS_CRON_LOG_DELETE, ["option" => $cronLogDelete, "option_id" => $cronLogDeleteOptionId], "no");

        $response["status"] = "success";
        $response["message"] = $cronLogDelete;
        wp_send_json_success($response);
    }

    public function logStatusChange() {
        check_ajax_referer( 'ir_ajax_nonce', 'nonce' );

        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $response = ["status" => "", "message" => ""];
        $logStatus = (bool) filter_input(INPUT_POST, "log_status", FILTER_SANITIZE_NUMBER_INT);
        
        if (!is_bool($logStatus)) {
            $response["status"] = "error";
            $response["message"] = esc_html__("The log status value is not valid", "redirect-redirection");
            wp_send_json_error($response);
        }
        
        update_option(self::OPTIONS_LOGS_STATUS, $logStatus, "yes");

        $response["status"] = "success";
        $response["message"] = "Log status updated";
        wp_send_json_success($response);
    }

    /**
     * Helper function to prepend protocol if missing
     */
    function prependProtocolIfNeeded($url) {
        $protocol = is_ssl() ? 'https://' : 'http://';
        if (!preg_match('#^(https?://)#', $url)) {
            $url = $protocol . $url;
        }
        return $url;
    }

    //***************************************************************************/
    //************************ REDIRECTION LOGS --END ***************************/
    //***************************************************************************/
}
