<?php

if (!defined("ABSPATH")) {
    exit();
}

class IRRPHelper implements IRRPConstants {

    /**
     * @var IRRPDBManager
     */
    private $dbManager;

    public function __construct($dbManager) {
        $this->dbManager = $dbManager;

        add_action(IRRP_CRON_DELETE_LOGS, [$this, "cronDeleteLogs"]);

        add_action("wp_loaded",[&$this, "logoutLoginRedirectHandler"],-20000);
        if (!empty($_SERVER["REQUEST_METHOD"]) && strtoupper($_SERVER["REQUEST_METHOD"]) === "GET") {
            add_action("wp_loaded", [&$this, "templateRedirect"], -20000);
            add_action("template_redirect", [&$this, "templateRedirect"], 1);
            add_action("admin_init", [&$this, "templateRedirect"], 1);
        }

        add_action("admin_post_irrp_delete_logs", [&$this, "deleteLogs"], 1);
        add_action("admin_post_irrp_download_logs", [&$this, "downloadLogs"], 1);
    }

    public function cronDeleteLogs() {
        $cronLogDelete = get_option(self::OPTIONS_CRON_LOG_DELETE, false);

        // check if option value is valid
        if (!$cronLogDelete) return;
        if (!$this->isDeleteOptionValid($cronLogDelete["option"])) {
            return;
        }

        // $olderThan = ($cronLogDelete["option"] === IrrPRedirection::$REDIRECTION_LOGS_DELETE[1]["option"]) ? DAY_IN_SECONDS : MONTH_IN_SECONDS;

        // when it will be WEEK_IN_SECONDS then the sql will be like this:
        // DELETE FROM wp_irrp_logs WHERE request_timestamp < current time stamp from one week ago
        // in the first it was like this:
        // DELETE FROM wp_irrp_logs WHERE request_timestamp < current time stamp from one day ago
        $olderThan = ($cronLogDelete["option"] === IrrPRedirection::$REDIRECTION_LOGS_DELETE[1]["option"]) ? WEEK_IN_SECONDS : MONTH_IN_SECONDS;
        $args = [
            "request_timestamp" => [
                "timestamp" => (int) (current_time("timestamp") - $olderThan),
                "compare" => "<"
            ]
        ];

        $this->dbManager->logDelete($args);
    }

    public function deleteLogs() {

        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $nonce = ($_nonce = filter_input(INPUT_GET, "_irrp_nonce", FILTER_SANITIZE_STRING)) ? $_nonce : "";
        $redirectTo = ($rTo = filter_input(INPUT_GET, "redirect_to", FILTER_SANITIZE_STRING)) ? $rTo : "";
        $logType = ($log_type = filter_input(INPUT_GET, "log_type", FILTER_SANITIZE_STRING)) ? $log_type : "";

        if (empty($nonce) || empty($redirectTo) || !wp_verify_nonce($nonce, self::nonceKey())) {
            return;
        }

        $args = [];
        if ($logType === "404s") {
            $args["response_code"] = 404;
        }

        $this->dbManager->logDelete($args);

        exit(wp_safe_redirect($redirectTo));
    }

    public function downloadLogs() {

        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $nonce = ($_nonce = filter_input(INPUT_GET, "_irrp_nonce", FILTER_SANITIZE_STRING)) ? $_nonce : "";
        $logType = ($log_type = filter_input(INPUT_GET, "log_type", FILTER_SANITIZE_STRING)) ? $log_type : "";

        if (empty($nonce) || !wp_verify_nonce($nonce, self::nonceKey())) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $args = ["limit" => -1, "orderby" => ["id"], "order" => "desc"];
        $filenamePart = "all";

        if ($logType === "404s") {
            $args["response_code"] = 404;
            $filenamePart = "404s";
        }

        $logs = $this->dbManager->logGet($args);

        if (empty($logs) || !is_array($logs)) {
            return;
        }

        $filename = "{$filenamePart}-logs--";
        $filename .= str_replace(["http://", "https://"], "", get_home_url()) . ".csv";

        header("Content-type: application/csv; charset=UTF-8");
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen("php://output", "w");

        $delimiter = ",";
        fputcsv($f, $this->getHumanReadableColumns(), $delimiter);

        foreach ($logs as $line) {
            fputcsv($f, $this->getHumanReadableValues($line), $delimiter);
        }
        fpassthru($f);
    }

    private function getHumanReadableColumns() {
        return [
            'URL visitors tried to access',
            'URL where they landed',
            'Date & Time',
            'Type'
        ];
    }

    private function getHumanReadableValues($log) {
        //$log_id = (int) $log["id"];
        //$log_redirect_id = empty($log["redirect_id"]) ? 0 : (int) $log["redirect_id"];
        $log_request_url = empty($log["request_url"]) ? "" : esc_html($log["request_url"]);
        $log_response_url = empty($log["response_url"]) ? "" : esc_html($log["response_url"]);
        $log_request_date = empty($log["request_date"]) ? "" : esc_html($log["request_date"]);
        //$log_request_timestamp = empty($log["request_timestamp"]) ? "" : (int) $log["request_timestamp"];
        $log_request_code = empty($log["request_code"]) ? 0 : (int) $log["request_code"];
        $log_response_code = empty($log["response_code"]) ? 0 : (int) $log["response_code"];
        $log_log_code = empty($log["log_code"]) ? "" : esc_html($log["log_code"]);
        //$log_extras = empty($log["extras"]) ? "" : maybe_unserialize($log["extras"]);
        //$log_accessed = $log_request_url;
        //$log_landed = $log_response_url;

        $response = $this->getRedirectData($log_request_url);
        $type = $log_response_code;
        if (($log_log_code === self::LOGCODE_IS_NOT_404_REDIRECT) ||
                ($log_response_code === 404 && $response["do_redirect"] === false) ||
                ($log_response_code === 404 && $response["do_redirect"] === true)) {
            $type = $log_response_code;
        } else if ($log_request_code === 404 && $response["do_redirect"] === true) {
            $type = "{$log_request_code} => {$log_response_code}";
        }


        return [
            $log_request_url,
            $log_response_url,
            $log_request_date,
            $type
        ];
    }

    public function isDeleteOptionValid($option) {
        return !empty($option) &&
                (strtolower($option) === IrrPRedirection::$REDIRECTION_LOGS_DELETE[1]["option"] ||
                strtolower($option) === IrrPRedirection::$REDIRECTION_LOGS_DELETE[2]["option"]);
    }

    public function buildPaginationHtml($countItems, $countPages, $currentOffset, $type = "redirection") {
        if ($countPages > 1) {
            include_once "settings/layouts/common/custom-pagination.php";
        }
    }

    public function buildRedirectsHtml($redirects, $selected = []) {
        $html = "";
        if (!empty($redirects) && is_array($redirects)) {
            ob_start();
            foreach ($redirects as $redirect) {
                $id = (int) $redirect["id"];
                $metas = $this->dbManager->getMeta($id);
                $redirectionType = esc_attr($redirect["type"]);
                $isIncExcEnabled = "";
                if ($redirectionType === self::TYPE_REDIRECTION) {
                    $from = esc_attr(wp_unslash($redirect["from"]));

                    // using in single-redirection.php file
                    $editClass = esc_attr("ir-edit-specific-redirect");
                    $fromDisabled = "";
                    $toDisabled = "";
                    $fromTitle = "";
                    $toTitle = "";
                    // using in single-redirection.php file -- END
                } else {
//                    $from = $metas[self::META_KEY_CRITERIAS][0]["value"];
                    // using in single-redirection.php file -- START
                    $fromDisabled = esc_attr("disabled='disabled' style='opacity:0.9;'");
                    $toDisabled = esc_attr("disabled='disabled' style='opacity:0.9;'");

                    $editClass = esc_attr("ir-edit-redirect-rule");
                    //$fromIndex = array_search($metas[self::META_KEY_CRITERIAS][0]["criteria"], array_column(IrrPRedirection::$CRITERIAS, "option"));
                    //$fromTitle = empty(IrrPRedirection::$CRITERIAS[$fromIndex]["text"]) ? "" : IrrPRedirection::$CRITERIAS[$fromIndex]["text"];
                    $toIndex = array_search($metas[self::META_KEY_ACTION]["name"], array_column(IrrPRedirection::$ACTIONS, "option"));
                    $toTitle = empty(IrrPRedirection::$ACTIONS[$toIndex]["text"]) ? "" : esc_attr(IrrPRedirection::$ACTIONS[$toIndex]["text"]);

                    $isIncExcEnabled = array_search("are-404s", array_column($metas[self::META_KEY_CRITERIAS], "criteria"));

                    if ($isIncExcEnabled === false) {
	                    $isIncExcEnabled = array_search("all-urls", array_column($metas[self::META_KEY_CRITERIAS], "criteria"));
                    }

                    // using in single-redirection.php file -- END
                }
                $match        = esc_attr(wp_unslash($redirect["match"]));
                $to           = esc_attr(wp_unslash($redirect["to"]));
                $redirectCode = (int) $metas["redirect_code"];
                $status       = checked((((int) $redirect["status"]) == 1), true, false);
                $timestamp    = (int) $redirect["timestamp"];
                $checked      = checked(in_array($id, $selected), true, false);
                $checked      = empty($checked) ? "" : $checked;
                $usage_count  = $this->dbManager->logCountUsage($id);
                include "settings/layouts/common/single-redirection.php";
            }
            $html = ob_get_clean();
        }
        return $html;
    }

    /**
     * display single log item html
     */
    public function buildLogsHtml($logs) {
        if (empty($logs) || !is_array($logs)) {
            return "";
        }

        ob_start();
        foreach ($logs as $log) {
            $log_id                = (int) $log["id"];
            $log_redirect_id       = empty($log["redirect_id"]) ? 0 : (int) $log["redirect_id"];
            $log_request_url       = empty($log["request_url"]) ? "" : esc_html($log["request_url"]);
            $log_response_url      = empty($log["response_url"]) ? "" : esc_html($log["response_url"]);
            $log_request_date      = empty($log["request_date"]) ? "" : esc_html($log["request_date"]);
            $log_request_timestamp = empty($log["request_timestamp"]) ? "" : (int) $log["request_timestamp"];
            $log_request_code      = empty($log["request_code"]) ? 0 : (int) $log["request_code"];
            $log_response_code     = empty($log["response_code"]) ? 0 : (int) $log["response_code"];
            $log_log_code          = empty($log["log_code"]) ? "" : esc_html($log["log_code"]);
            $log_count             = empty($log["count"]) ? 1 : (int) $log["count"];
            $log_extras            = empty($log["extras"]) ? "" : maybe_unserialize($log["extras"]);

//            $log_request_parsed = parse_url($log_request_url);
//            $log_request_path = empty($log_request_parsed["path"]) ? $log_request_url : $log_request_parsed["path"];
//            $log_request_query = empty($log_request_parsed["query"]) ? "" : $log_request_parsed["query"];
//            $log_response_parsed = parse_url($log_response_url);
//            $log_response_path = empty($log_response_parsed["path"]) ? $log_response_url : $log_response_parsed["path"];
//            $log_response_query = empty($log_response_parsed["query"]) ? "" : $log_response_parsed["query"];
//            if (empty($log_request_parsed["query"])) {
//                $log_accessed = $log_request_path;
//            } else {
//                $log_accessed = "{$log_request_path}?{$log_request_query}";
//            }

            $log_accessed = $log_request_url;

//            $log_landed = empty($log_response_parsed["query"]) ? $log_response_path : "{$log_response_path}?{$log_response_query}";

            $log_landed = $log_response_url;

            $response = $this->getRedirectData($log_request_url);

            $redirect_request_timestamp = empty($response["redirect"]["timestamp"]) ? 0 : (int) $response["redirect"]["timestamp"];

            $from = $response["current_url"];
            $to = $response["to"];

            $redirect_timestamp = empty($response['redirect']['timestamp']) ? false : (int) $response['redirect']['timestamp'];

            $is404Cls = ($log_response_code === 404 && !$response["do_redirect"]) ? "ir-log-404" : "";
            include "settings/layouts/redirection-and-404-logs/single-log.php";
        }
        $html = ob_get_clean();
        return $html;
    }

    public static function customDropdown($ddName, $ddOptions, $selected, $args = []) {
        include "settings/layouts/common/custom-dropdown.php";
    }

    public function logoutLoginRedirectHandler() {
        $force_stop_redirect = apply_filters('irrp_force_stop_redirect', false);
        if ($force_stop_redirect) {
            return; // stopping...
        }

        if($this->dbManager->isLogMeWhereIFinishedEnabled()){ 
            if($this->isLoggingOut()){
                $redirectUrl = wp_get_referer();
                if (strpos($redirectUrl, 'wp-admin') !== false && (current_user_can('manage_options') || current_user_can('redirect_redirection_admin'))) {
                    $this->dbManager->addRefererUrl( get_current_user_id() ,$redirectUrl);
                }
            }
            add_filter('logout_redirect', [$this, 'loggedOutRedirectionUrl'], 10, 3);
            add_filter('login_redirect', [$this, 'loggedInRedirectionUrl'], 10, 3);
        }

    }
    public function templateRedirect($requested_url = null, $do_redirect = true) {
    	$force_stop_redirect = apply_filters('irrp_force_stop_redirect', false);
    	if ($force_stop_redirect) {
    		return; // stopping...
	    }

        $response = $this->getRedirectData();
        if (apply_filters("irrp_log_requests", true)) {
            $this->logRequest($response);
        }

        //todo
        $this->doRedirect($response);
        //todo
    }

    public function getRedirectData($requestedUrl = "") {
        $response = [
            "do_redirect" => false,
            "to" => "",
            "is_404" => is_404(),
            "current_url" => "",
            "redirect_code" => "",
            "redirect" => null,
            "redirect_metas" => null
        ];

        $requestData = empty($requestedUrl) ? parse_url($_SERVER["REQUEST_URI"]) : parse_url($requestedUrl);
        $requestPath = empty($requestData["path"]) ? "" : trim($requestData["path"]);
        $requestQuery = empty($requestData["query"]) ? "" : trim($requestData["query"]);

        if ($requestPath) {
            $request = $requestPath;
            
            // this search for matching b/w "uri_path" and "match" col in db table => foundRow|null
            $redirect = $this->dbManager->getMatched($request);

            $protocol = $this->getProtocol();
            $response["current_url"] = $protocol . $_SERVER["HTTP_HOST"] . ($requestQuery ? "{$requestPath}?{$requestQuery}" : $requestPath);
            $response["current_url"] = trailingslashit($response["current_url"]);

            // specific URL's
            if ($redirect) {

                $continueCheck = true;
                $response["redirect"] = $redirect;
                $redirectId = (int) $redirect["id"];
                $metas = $this->dbManager->getMeta($redirectId);
                $response["redirect_metas"] = $metas;
                $match = $redirect["match"];
                $matchQuery = parse_url($redirect["from"], PHP_URL_QUERY);

                if ($metas["ignore_case"]) {
                    if (function_exists("mb_strtolower")) {
                        $matchQuery = mb_strtolower($matchQuery);
                        $requestQuery = mb_strtolower($requestQuery);
                        $request = mb_strtolower($request);
                        $match = mb_strtolower($match);
                    } else {
                        $matchQuery = strtolower($matchQuery);
                        $requestQuery = strtolower($requestQuery);
                        $request = strtolower($request);
                        $match = strtolower($match);
                    }
                }

                if (!$metas["ignore_parameters"] && ($requestQuery || $matchQuery)) {

                    parse_str($matchQuery, $matchQueryArr);
                    parse_str($requestQuery, $requestQueryArr);

                    if ($matchQuery && $requestQuery) {
                        if ($matchQueryArr != $requestQueryArr) {
                            $continueCheck = false;
                        }
                    } else {
                        $continueCheck = false;
                    }
                }



                if ($continueCheck) {

                    if ($metas["ignore_trailing_slashes"]) {
                        $request = rtrim($request, "/");
                        $match = rtrim($match, "/");
                    }

                    $response["do_redirect"] = (untrailingslashit($request) === untrailingslashit($match));

                    $response["to"] = $redirect["to"];
                    $response["redirect_code"] = $redirectCode = empty($metas["redirect_code"]) ? 301 : (int) $metas["redirect_code"];
                    if ($metas["pass_on_parameters"] && $requestQuery) {
                        $toQuery = parse_url($response["to"], PHP_URL_QUERY);
                        if ($toQuery) {
                            $response["to"] .= ("&" === substr($response["to"], -1)) ? $requestQuery : ("&" . $requestQuery);
                        } else {
                            $response["to"] = rtrim($response["to"], "?") . "?" . $requestQuery;
                        }
                    }

                    if ($response["do_redirect"]) {
                        $response["do_redirect"] = $this->applyIncExcRules($metas, $response["do_redirect"]);
                    }
                }
            }
            // redirection rules
            else {
                // ********************************************************
                $request = $response["current_url"];
                $redirects = $this->dbManager->getRules();

                if ($redirects && is_array($redirects)) {
                    foreach ($redirects as $redirect) {
                        $response["redirect"] = $redirect;
                        $redirectId = (int) $redirect["id"];
                        $metas = $this->dbManager->getMeta($redirectId);
                        $response["redirect_metas"] = $metas;
                        $response["to"] = $redirect["to"];

                        $matchQuery = parse_url($redirect["from"], PHP_URL_QUERY);
                        $criterias = $metas[self::META_KEY_CRITERIAS];
                        $action = $metas[self::META_KEY_ACTION];

                        foreach ($criterias as $k => $c) {
                            $criteria = $c["criteria"];
                            $from = $c["value"];
                            $match = $c["value"];

                            if (!$metas["ignore_parameters"] && $requestQuery) {
                                break;
                            }

                            if ($metas["ignore_trailing_slashes"]) {

                                if ($requestQuery) {
                                    $request = rtrim(str_replace("?" . $requestQuery, "", $request), "/") . "?" . $requestQuery;
                                } else {
                                    $request = rtrim($request, "/");
                                }

                                if ($matchQuery) {
                                    $from = rtrim(str_replace("?" . $matchQuery, "", $from), "/") . "?" . $matchQuery;
                                    $match = rtrim(str_replace("?" . $matchQuery, "", $match), "/") . "?" . $matchQuery;
                                } else {
                                    $from = rtrim($from, "/");
                                    $match = rtrim($match, "/");
                                }
                            }

                            if ($metas["ignore_case"]) {
                                $request = function_exists("mb_strtolower") ? mb_strtolower($request) : strtolower($request);
                                if ($criteria !== "regex-match") {
                                    if (function_exists("mb_strtolower")) {
                                        $match = mb_strtolower($match);
                                        $from = mb_strtolower($from);
                                    } else {
                                        $match = strtolower($match);
                                        $from = strtolower($from);
                                    }
                                }
                            }

                            if ($criteria === "contain") {
                                $searchStr = untrailingslashit(str_replace("?" . $matchQuery, "", $from));
                                if ($searchStr) {
                                    $response["do_redirect"] = (strpos($request, $searchStr) !== false);
                                }

                                if ($response["do_redirect"]) {
                                    if ($action["name"] === "urls-with-new-string") {
                                        if ($k === 0) {
                                            $response["to"] = str_replace($from, $response["to"], $request);
                                        } else {
                                            $response["to"] = str_replace($from, "", $response["to"]);
                                        }
                                    } else if ($action["name"] === "urls-with-removed-string") {
                                        $response["to"] = str_replace($from, "", $request);
                                    }
                                }
                            }
                            //
                            else if ($criteria === "start-with") {

                                $fromProtocol = "";
                                $fromParsed = parse_url($from);
                                if (empty($fromParsed["scheme"])) {
                                    $fromProtocol = is_ssl() ? "https:" : "http:";
                                }

                                if ($fromProtocol && (substr($from, 0, 2) !== "//")) {
                                    $fromProtocol .= "//";
                                }
                                $from = $fromProtocol . $from;
                                $response["do_redirect"] = preg_match("#^" . preg_quote(untrailingslashit($from)) . "#s", $request);
                                //
                            }
                            //
                            else if ($criteria === "end-with") {
                                $response["do_redirect"] = preg_match("#" . preg_quote(untrailingslashit($from)) . "$#s", $request);
                                //
                                if ($action["name"] === "specific-url") {
                                    $response["to"] = $action["value"];
                                }else if ($action["name"] === "urls-with-new-string") {
                                    $response["to"] = str_replace($from, $response["to"], $request);
                                } else if ($action["name"] === "urls-with-removed-string") {
                                    $response["to"] = str_replace($from, "", $request);
                                }
                            }
                            //
                            else if ($criteria === "regex-match"/* && (strpos($request, '/wp-admin') === false)*/) {
                                $caseModifier = $metas["ignore_case"] ? "i" : "";
                                $pattern = "#" . (untrailingslashit($from)) . "#s" . $caseModifier;
                                $response["do_redirect"] = @preg_match($pattern, $request);
                                if ($action["name"] === "regex-match" && $response["do_redirect"]) {
                                    $response["to"] = preg_replace($pattern, $response["to"], $request);
                                }
                                //
                            }
                            //
                            else if ($criteria === "have-permalink-structure" && $action["name"] === "new-permalink-structure") {

                                if ($response["is_404"]) {
                                    $name = "";
                                    $year = "";
                                    $month = "";
                                    $day = "";
                                    $matches = [];
                                    if ($match === "day-and-name") {
                                        $pattern = "#/([\d+]{4})/([\d+]{2})/([\d+]{2})/([^/\?]+/?).*?#su";

                                        if (preg_match($pattern, $request, $matches)) {
                                            $matches = array_values($matches);
                                            if ((int) $matches[1])
                                                $year = $matches[1];

                                            if ((int) $matches[2])
                                                $month = $matches[2];

                                            if ((int) $matches[3])
                                                $day = $matches[3];

                                            if (!empty($matches[4]))
                                                $name = $matches[4];
                                        }
                                    } else if ($match === "month-and-name") {
                                        $pattern = "#/([\d+]{4})/([\d+]{2})/([^/\?]+/?).*?#su";
                                        if (preg_match($pattern, $request, $matches)) {
                                            $matches = array_values($matches);
                                            if ((int) $matches[1])
                                                $year = $matches[1];

                                            if ((int) $matches[2])
                                                $month = $matches[2];

                                            if (trim($matches[3]))
                                                $name = $matches[3];
                                        }
                                    } else if ($match === "post-name") {
                                        $pattern = "#/([^/\?]+/?)(?:\?.*)?$#su";
                                        if (preg_match($pattern, $request, $matches)) {
                                            $matches = array_values($matches);
                                            if (trim($matches[1]))
                                                $name = $matches[1];
                                        }
                                    }

                                    $postId = $this->dbManager->getPostBySlug(trim($name, "/"), $year, $month, $day);
                                    $post = get_post($postId);
                                    if ($postId && $post) {
                                        $response["to"] = get_permalink($postId);
                                        $response["do_redirect"] = true;
                                        $postname = $post->post_name;
                                        if ($metas["ignore_trailing_slashes"]) {
                                            $name = trim($name, "/");
                                            $postname = trim($postname, "/");
                                        }

                                        if ($metas["ignore_case"]) {
                                            if (function_exists("mb_strtolower")) {
                                                $name = mb_strtolower($name);
                                                $postname = mb_strtolower($postname);
                                            } else {
                                                $name = strtolower($name);
                                                $postname = strtolower($postname);
                                            }
                                        }

                                        $response["do_redirect"] = ($name === $postname);
                                        if (!$metas["ignore_parameters"]) {
                                            $response["do_redirect"] = (trim($requestQuery) === "");
                                        }
                                    }
                                }
                                //
                            }
                            //
                            else if ($criteria === "are-404s" && $action["name"] === "a-specific-url") {
                                if ($response["is_404"]) {
                                    $response["to"] = $action["value"];
                                    $response["do_redirect"] = true;
                                }
                            }
                            //
                            else if ($criteria === "are-404s" && $action["name"] === "random-similar-post") {
                                $permalinkStructure = get_option("permalink_structure");

                                if ($response["is_404"] && $permalinkStructure) {
                                    global $wp_query;

                                    $postId = 0;
                                    $postType = "";

                                    if (!empty($wp_query->query_vars["name"])) { // slug exists
                                        $slug = trim(sanitize_text_field($wp_query->query_vars["name"]));
                                        $postId = $this->dbManager->getBestMatchedIDBySlug($slug);
                                    }

                                    if ($postId) {
                                        $postType = get_post_type($postId);
                                    } else {
                                        $posts = get_posts(["number" => 1, "orderby" => "date", "order" => "desc"]);
                                        if (!empty($posts[0])) {
                                            $postId = (int) $posts[0]->ID;
                                            $postType = $posts[0]->post_type;
                                        }
                                    }

                                    if ($postId && $postType) {
                                        $response["to"] = in_array($postType, ["post", "page", "attachment"]) ? get_permalink($postId) : get_post_permalink($postId);
                                        $response["do_redirect"] = true;
                                    }
                                }
                            } else if ($criteria === "all-urls" && $action["name"] === "a-specific-url" && (strpos($request, '/wp-admin') === false) && trim($request, '/') !== trim($action['value'], '/')) {
	                            $response["to"] = $action['value'];
	                            $response["do_redirect"] = true;
                            }

                            $response["redirect_code"] = $redirectCode = empty($metas["redirect_code"]) ? 301 : (int) $metas["redirect_code"];
                            if ($requestQuery) {
                                if ($metas["pass_on_parameters"]) {
                                    /* uneccessary due to params passed automatically */
                                    $toQuery = parse_url($response["to"], PHP_URL_QUERY);
                                    if ($toQuery) {
                                       $response["to"] .= ("&" === substr($response["to"], -1)) ? $requestQuery : ("&" . $requestQuery);
                                    } else {
                                       $response["to"] = rtrim($response["to"], "?") . "?" . $requestQuery;
                                    }
                                } else {
                                    // removing query params
                                    if ($metas["ignore_case"]) {
                                        if (function_exists("mb_strtolower")) {
                                            $response["to"] = str_replace("?" . mb_strtolower($requestQuery), "", mb_strtolower($response["to"]));
                                        } else {
                                            $response["to"] = str_replace("?" . strtolower($requestQuery), "", strtolower($response["to"]));
                                        }
                                    } else {
                                        $response["to"] = str_replace("?" . $requestQuery, "", $response["to"]);
                                    }
                                }
                            }
                            if (!$response["do_redirect"]) {
                                break;
                            }
                        }
                        //
                        if ($response["do_redirect"]) {
                            $response["do_redirect"] = $this->applyIncExcRules($metas, $response["do_redirect"]);
                            if ($response["do_redirect"]) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        static $recursion_protection = false;

        if(! $response["do_redirect"] && ! $recursion_protection) {
            $recursion_protection = true;

            $encoded_url = sprintf("%s%s%s%s%s",
                empty($requestData["scheme"]) ? "" : $requestData["scheme"] . "://",
                empty($requestData["host"]) ? "" : $requestData["host"],
                empty($requestData["port"]) ? "" : ":" . $requestData["port"],
                rawurlencode($requestPath),
                $requestQuery ? "?" . strtolower(rawurlencode($requestQuery)) : ""
            );

            $response = $this->getRedirectData($encoded_url);

            if(! $response["do_redirect"]){
                $decoded_url = sprintf("%s%s%s%s%s",
                    empty($requestData["scheme"]) ? "" : $requestData["scheme"] . "://",
                    empty($requestData["host"]) ? "" : $requestData["host"],
                    empty($requestData["port"]) ? "" : ":" . $requestData["port"],
                    rawurldecode($requestPath),
                    $requestQuery ? "?" . strtolower(rawurldecode($requestQuery)) : ""
                );

                $response = $this->getRedirectData($decoded_url);
            }

            return $response;
        }

        // iC: Quick solution for looped redirects - can be refactored later
        if ($response['do_redirect'] == true && isset($response['to']) && isset($response['current_url'])) {

          $resToURL = untrailingslashit($response['to']);
          $resCurrURL = untrailingslashit($response['current_url']);
          $wpAdminURL = untrailingslashit(admin_url());
          $wpLoginURL = untrailingslashit(site_url('wp-login.php'));

          if ($resToURL == $resCurrURL) {
            $response['do_redirect'] = false;
          }

          if ($resCurrURL == $wpLoginURL) {
            $response['do_redirect'] = false;
          }

          if (substr($resCurrURL, 0, strlen($wpAdminURL)) === $wpAdminURL) {
            $response['do_redirect'] = false;
          }

          if (strpos($resCurrURL, '?redirect-plugin=disable_all_redirects') !== false) {
            $response['do_redirect'] = false;
          }

          if ($this->isExcludedUrl($resCurrURL)) {
            $response['do_redirect'] = false;
          }

        }

        return $response;
    }

    private function logRequest($response) {

        if (empty($response["current_url"])) {
            return;
        }

        $isAddLog = false;
        $logData = [
            "redirect_id" => $response["redirect"] ? $response["redirect"]["id"] : 0,
            "request_url" => esc_url_raw($response["current_url"]),
            "response_url" => "",
            "request_date" => current_time("mysql"),
            "request_timestamp" => current_time("timestamp"),
            "request_code" => 0,
            "response_code" => 0,
            "log_code" => "",
            "extras" => "",
        ];

        // Case when visitor landed on a 404 and user has not defined 404 redirection rule which would fix this
        if ($response["is_404"] && !$response["do_redirect"]) {
            $isAddLog = true;
            $logData["response_url"] = "404";
            $logData["request_code"] = "404";
            $logData["response_code"] = "404";
            $logData["log_code"] = self::LOGCODE_IS_404_NO_REDIRECT;
        }
        // Case when user had defined a 404 redirection rule before visitor arrived on the url, so he got redirected to a working page.
        else if ($response["is_404"] && $response["do_redirect"] && $response["to"]) {
            $isAddLog = true;
            $logData["response_url"] = esc_url_raw($response["to"]);
            $logData["request_code"] = "404";
            $logData["response_code"] = $response["redirect_code"];
            $logData["log_code"] = self::LOGCODE_IS_404_REDIRECT;
        }
        // Case when visitor arrived on a working url but got redirected according to 301 rule.
        else if (!$response["is_404"] && $response["do_redirect"] && $response["to"]) {
            $isAddLog = true;
            $logData["response_url"] = esc_url_raw($response["to"]);
            $logData["request_code"] = "200";
            $logData["response_code"] = $response["redirect_code"];
            $logData["log_code"] = self::LOGCODE_IS_NOT_404_REDIRECT;
        }

        if ($isAddLog) {
            $this->dbManager->logAdd($logData);
        }
    }

    private function doRedirect($response) {
        if (empty($response["do_redirect"]) || empty($response["to"]) || empty($response["redirect_code"])) {
            return;
        }

        $redirection_http_headers = isset($response['redirect_metas']['redirection_http_headers']) ? $response['redirect_metas']['redirection_http_headers'] : '';

        $headers = [];
        if ( ! headers_sent()  && !empty($redirection_http_headers)) {
            // If not valid JSON, then split the string using ',' and then parse the headers as key:value pairs
            $redirection_http_headers = explode(",", $redirection_http_headers);
            foreach ($redirection_http_headers as $header) {
                $header = explode(":", $header);

                // Skips invalid headers
                if(!isset($header[0]) || !isset($header[1])) {
                    continue;
                }

                $headers[trim($header[0])] = trim($header[1]);

                // iC: It will never work, it's not how browser work, you send
                // these requests as response not as custom header for redirect
                // header(sprintf("%s:%s", trim($header[0]), trim($header[1])));
            }
        }

        $response["to"] = add_query_arg($headers, $response["to"]);
        
        if (substr($response["to"], -3) === "%2F") {
            $response["to"] = substr($response["to"], 0, -3);
        }
        if (substr($response["to"], -3) === "%2f") {
            $response["to"] = substr($response["to"], 0, -3);
        }

        wp_redirect($response["to"], $response["redirect_code"]);
        exit();
    }

    private function applyIncExcRules($metas, $doRedirect) {
        if ($metas["inclusion_exclusion_rules"]) {
            $rulesGroup = [];
            if ($metas["redirect_options"] === "are_case") { //============== ARE CASE ==============//
                // login info - are case
                if ($metas["rules_group1"]["enabled"]) {
                    $loginInfo = $metas["rules_group1"]["login_info"];
                    if ($loginInfo === "logged_in") {
                        $rulesGroup[] = is_user_logged_in();
                    } else if ($loginInfo === "not_logged_in") {
                        $rulesGroup[] = !is_user_logged_in();
                    }
                }

                // has or not specific wp user role - are case
                if ($metas["rules_group2"]["enabled"]) {
                    $currentUser = wp_get_current_user();
                    $role = $metas["rules_group2"]["role"];
                    $roleName = $metas["rules_group2"]["role_name"];
                    $roleNameArray = json_decode(stripslashes($roleName), ARRAY_A);
                    if (is_array($roleNameArray)) { // is multiple roles
                        $ruleGroup2 = false;
                        foreach ($roleNameArray as $roleName) {
                            if ($role === "has") {
                                $ruleGroup2 = is_user_logged_in() && is_array($currentUser->roles) && in_array($roleName, $currentUser->roles);
                            } else if ($role === "does_not_have") {
                                $ruleGroup2 = !is_user_logged_in() || (is_array($currentUser->roles) && !in_array($roleName, $currentUser->roles));
                            }
                            if ($ruleGroup2) {
                                break;
                            }
                        }
                        $rulesGroup[] = $ruleGroup2;
                    }else{
                        if ($role === "has") {
                            $rulesGroup[] = is_user_logged_in() && is_array($currentUser->roles) && in_array($roleName, $currentUser->roles);
                        } else if ($role === "does_not_have") {
                            $rulesGroup[] = !is_user_logged_in() || (is_array($currentUser->roles) && !in_array($roleName, $currentUser->roles));
                        }
                    }
                }

                // HTTP_REFERER - are case
                if ($metas["rules_group3"]["enabled"]) {
                    $referrerMatch = $metas["rules_group3"]["referrer"];
                    $referrerValue = $metas["rules_group3"]["referrer_value"];
                    $referrerRegex = isset($metas["rules_group3"]["referrer_regex"]) ? $metas["rules_group3"]["referrer_regex"] : false;
                    $referrer = empty($_SERVER["HTTP_REFERER"]) ? "" : trim($_SERVER["HTTP_REFERER"]);
                    if ($referrerMatch === "matches") {
                        $rulesGroup[] = $referrerRegex ? preg_match("#" . stripslashes($referrerValue) . "#isu", $referrer) : ($referrerValue === $referrer);
                    } else if ($referrerMatch === "does_not_match") {
                        $rulesGroup[] = $referrerRegex ? !preg_match("#" . stripslashes($referrerValue) . "#isu", $referrer) : ($referrerValue !== $referrer);
                    }
                }

                // USER_AGENT - are case
                if ($metas["rules_group4"]["enabled"]) {
                    $agentMatch = $metas["rules_group4"]["agent"];
                    $agentValue = $metas["rules_group4"]["agent_value"];
                    $agentRegex = $metas["rules_group4"]["agent_regex"];
                    $agent = empty($_SERVER["HTTP_USER_AGENT"]) ? "" : trim($_SERVER["HTTP_USER_AGENT"]);

                    if ($agentMatch === "matches") {
                        $rulesGroup[] = $agentRegex ? preg_match("#" . stripslashes($agentValue) . "#isu", $agent) : ($agentValue === $agent);
                    } else if ($agentMatch === "does_not_match") {
                        $rulesGroup[] = $agentRegex ? !preg_match("#" . stripslashes($agentValue) . "#isu", $agent) : ($agentValue !== $agent);
                    }
                }

                // COOKIE - are case
                if ($metas["rules_group5"]["enabled"]) {
                    $cookieMatch = $metas["rules_group5"]["cookie"];
                    $cookieRegex = $metas["rules_group5"]["cookie_regex"];
                    $cookieName = stripslashes($metas["rules_group5"]["cookie_name"]);
                    $cookieValue = $cookieRegex ? $metas["rules_group5"]["cookie_value"] : urldecode(stripslashes($metas["rules_group5"]["cookie_value"]));

                    $cookies = empty($_COOKIE) ? [] : array_map("sanitize_text_field", $_COOKIE);

                    if ($cookieMatch === "matches") {
                        if ($cookieRegex) {
                            foreach ($cookies as $name => $cookie) {
                                $cookieRegexName = preg_match("#" . $cookieName . "#isu", $name);
                                $cookieRegexValue = preg_match("#" . $cookieValue . "#isu", $cookie);
                                if ($cookieRegexName && $cookieRegexValue) {
                                    $rulesGroup[] = true;
                                    break;
                                }
                            }
                        } else {
                            $rulesGroup[] = isset($cookies[$cookieName]) && $cookies[$cookieName] === $cookieValue;
                        }
                    } else if ($cookieMatch === "does_not_match") {
                        if ($cookieRegex) {
                            $cookieNotFound = true;
                            foreach ($cookies as $name => $cookie) {
                                $cookieRegexName = preg_match("#" . $cookieName . "#isu", $name);
                                $cookieRegexValue = preg_match("#" . $cookieValue . "#isu", $cookie);
                                if ($cookieRegexName || $cookieRegexValue) {
                                    $cookieNotFound = false;
                                    break;
                                }
                            }
                            $rulesGroup[] = $cookieNotFound;
                        } else {
                            $rulesGroup[] = !isset($cookies[$cookieName]) || (isset($cookies[$cookieName]) && $cookies[$cookieName] != $cookieValue);
                        }
                    }
                }

                // REMOTE_ADDR - are case
                if ($metas["rules_group6"]["enabled"]) {
                    $ipMatch = $metas["rules_group6"]["ip"];
                    $ipValue = $metas["rules_group6"]["ip_value"];
                    $ip = empty($_SERVER["REMOTE_ADDR"]) ? "" : trim($_SERVER["REMOTE_ADDR"]);
                    if ($ipMatch === "matches") {
                        $rulesGroup[] = ($ipValue === $ip);
                    } else if ($ipMatch === "does_not_match") {
                        $rulesGroup[] = ($ipValue !== $ip);
                    }
                }

                // SERVER_NAME - are case
                if ($metas["rules_group7"]["enabled"]) {
                    $serverMatch = $metas["rules_group7"]["server"];
                    $serverValue = $metas["rules_group7"]["server_value"];
                    $server = empty($_SERVER["SERVER_NAME"]) ? "" : trim($_SERVER["SERVER_NAME"]);
                    if ($serverMatch === "matches") {
                        $rulesGroup[] = ($serverValue === $server);
                    } else if ($serverMatch === "does_not_match") {
                        $rulesGroup[] = ($serverValue !== $server);
                    }
                }

                // BROWSER LANGUAGE - are case
                if ($metas["rules_group8"]["enabled"]) {
                    $languageMatch = $metas["rules_group8"]["language"];
                    $languageValues = array_map("strtolower", explode(",", trim($metas["rules_group8"]["language_value"])));
                    $browserLanguage = empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? "*" : strtolower(trim($_SERVER["HTTP_ACCEPT_LANGUAGE"]));
                    $commaPosition = strpos($browserLanguage, ",");
                    $browserLanguageSubstr = ($commaPosition !== false) ? substr($browserLanguage, 0, $commaPosition) : $browserLanguage;

                    if ($languageMatch === "matches") {
                        // check browser language and user filled language in settings w/o making any changes on them
                        $languageMatches = in_array($browserLanguageSubstr, $languageValues) || $browserLanguage === "*";

                        // if languages does not match, substring browser language
                        if (!$languageMatches) {
                            $browserLanguageSubstr = substr($browserLanguageSubstr, 0, 2);
                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                        }

                        // if languages does not match, substring defined languages and check again
                        if (!$languageMatches) {
                            $languageValues = array_map(function ($languageValue) {
                                return substr($languageValue, 0, 2);
                            }, $languageValues);

                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                        }

                        $rulesGroup[] = $languageMatches;
                    } else if ($languageMatch === "does_not_match") {
                        $languageMatches = in_array($browserLanguageSubstr, $languageValues) && $browserLanguage !== "*";

                        if (!$languageMatches) {
                            $browserLanguageSubstr = substr($browserLanguageSubstr, 0, 2);
                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);

                            if (!$languageMatches) {
                                $languageValues = array_map(function ($languageValue) {
                                    return substr($languageValue, 0, 2);
                                }, $languageValues);

                                $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                            }
                        }

                        $rulesGroup[] = !$languageMatches;
                    }
                }

                ////////////////////////////////////////
            } else { //============== ARE NOT CASE ==============//
                // login info - are not case
                if ($metas["rules_group1"]["enabled"]) {
                    $loginInfo = $metas["rules_group1"]["login_info"];
                    if ($loginInfo === "logged_in") {
                        $rulesGroup[] = !is_user_logged_in();
                    } else if ($loginInfo === "not_logged_in") {
                        $rulesGroup[] = is_user_logged_in();
                    }
                }

                // has or not specific wp user role  - are not case
                if ($metas["rules_group2"]["enabled"]) {
                    $currentUser = wp_get_current_user();
                    $role = $metas["rules_group2"]["role"];
                    $roleName = $metas["rules_group2"]["role_name"];
                    if ($role === "has") {
                        $rulesGroup[] = !(is_user_logged_in() && is_array($currentUser->roles) && in_array($roleName, $currentUser->roles));
                    } else if ($role === "does_not_have") {
                        $rulesGroup[] = !is_user_logged_in() || !(is_array($currentUser->roles) && !in_array($roleName, $currentUser->roles));
                    }
                }


                // HTTP_REFERER - are not case
                if ($metas["rules_group3"]["enabled"]) {
                    $referrerMatch = $metas["rules_group3"]["referrer"];
                    $referrerValue = $metas["rules_group3"]["referrer_value"];
                    $referrer = empty($_SERVER["HTTP_REFERER"]) ? "" : trim($_SERVER["HTTP_REFERER"]);
                    if ($referrerMatch === "matches") {
                        $rulesGroup[] = !($referrerValue === $referrer);
                    } else if ($referrerMatch === "does_not_match") {
                        $rulesGroup[] = !($referrerValue !== $referrer);
                    }
                }

                // USER_AGENT - are not case
                if ($metas["rules_group4"]["enabled"]) {
                    $agentMatch = $metas["rules_group4"]["agent"];
                    $agentValue = $metas["rules_group4"]["agent_value"];
                    $agentRegex = $metas["rules_group4"]["agent_regex"];
                    $agent = empty($_SERVER["HTTP_USER_AGENT"]) ? "" : trim($_SERVER["HTTP_USER_AGENT"]);

                    if ($agentMatch === "matches") {
                        $rulesGroup[] = $agentRegex ? !preg_match("#" . stripslashes($agentValue) . "#isu", $agent) : !($agentValue === $agent);
                    } else if ($agentMatch === "does_not_match") {
                        $rulesGroup[] = $agentRegex ? preg_match("#" . stripslashes($agentValue) . "#isu", $agent) : !($agentValue !== $agent);
                    }
                }

                // COOKIE - are not case
                if ($metas["rules_group5"]["enabled"]) {
                    $cookieMatch = $metas["rules_group5"]["cookie"];
                    $cookieRegex = $metas["rules_group5"]["cookie_regex"];
                    $cookieName = stripslashes($metas["rules_group5"]["cookie_name"]);
                    $cookieValue = $cookieRegex ? $metas["rules_group5"]["cookie_value"] : urldecode(stripslashes($metas["rules_group5"]["cookie_value"]));

                    $cookies = empty($_COOKIE) ? [] : array_map("sanitize_text_field", $_COOKIE);

                    if ($cookieMatch === "matches") {
                        if ($cookieRegex) {
                            foreach ($cookies as $name => $cookie) {
                                $cookieRegexName = !preg_match("#" . $cookieName . "#isu", $name);
                                $cookieRegexValue = !preg_match("#" . $cookieValue . "#isu", $cookie);
                                if ($cookieRegexName && $cookieRegexValue) {
                                    $rulesGroup[] = true;
                                    break;
                                }
                            }
                        } else {
                            $rulesGroup[] = !isset($cookies[$cookieName]) && !in_array($cookieValue, $cookies);
                        }
                    } else if ($cookieMatch === "does_not_match") {
                        if ($cookieRegex) {
                            $cookieNotFound = true;
                            foreach ($cookies as $name => $cookie) {
                                $cookieRegexName = preg_match("#" . $cookieName . "#isu", $name);
                                $cookieRegexValue = preg_match("#" . $cookieValue . "#isu", $cookie);
                                if ($cookieRegexName || $cookieRegexValue) {
                                    $cookieNotFound = false;
                                    break;
                                }
                            }
                            $rulesGroup[] = !$cookieNotFound;
                        } else {
                            $rulesGroup[] = isset($cookies[$cookieName]) && $cookies[$cookieName] == $cookieValue;
                        }
                    }
                }

                // REMOTE_ADDR - are not case
                if ($metas["rules_group6"]["enabled"]) {
                    $ipMatch = $metas["rules_group6"]["ip"];
                    $ipValue = $metas["rules_group6"]["ip_value"];
                    $ip = empty($_SERVER["REMOTE_ADDR"]) ? "" : trim($_SERVER["REMOTE_ADDR"]);
                    if ($ipMatch === "matches") {
                        $rulesGroup[] = !($ipValue === $ip);
                    } else if ($ipMatch === "does_not_match") {
                        $rulesGroup[] = !($ipValue !== $ip);
                    }
                }

                // SERVER_NAME - are not case
                if ($metas["rules_group7"]["enabled"]) {
                    $serverMatch = $metas["rules_group7"]["server"];
                    $serverValue = $metas["rules_group7"]["server_value"];
                    $server = empty($_SERVER["SERVER_NAME"]) ? "" : trim($_SERVER["SERVER_NAME"]);
                    if ($serverMatch === "matches") {
                        $rulesGroup[] = !($serverValue === $server);
                    } else if ($serverMatch === "does_not_match") {
                        $rulesGroup[] = !($serverValue !== $server);
                    }
                }

                // BROWSER LANGUAGE - are not case
                if ($metas["rules_group8"]["enabled"]) {
                    $languageMatch = $metas["rules_group8"]["language"];
                    $languageValues = array_map("strtolower", explode(",", trim($metas["rules_group8"]["language_value"])));
                    $browserLanguage = empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? "*" : strtolower(trim($_SERVER["HTTP_ACCEPT_LANGUAGE"]));
                    $browserLanguageSubstr = strlen($browserLanguage) > 1 ? substr($browserLanguage, 0, strpos($browserLanguage, ",")) : "";

                    if ($languageMatch === "matches") {
                        // check browser language and user filled language in settings w/o making any changes on them
                        $languageMatches = in_array($browserLanguageSubstr, $languageValues) || $browserLanguage === "*";

                        // if languages does not match, substring browser language
                        if (!$languageMatches) {
                            $browserLanguageSubstr = substr($browserLanguageSubstr, 0, 2);
                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                        }

                        // if languages does not match, substring defined languages and check again
                        if (!$languageMatches) {
                            $languageValues = array_map(function ($languageValue) {
                                return substr($languageValue, 0, 2);
                            }, $languageValues);

                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                        }

                        $rulesGroup[] = !$languageMatches;
                    } else if ($languageMatch === "does_not_match") {
                        $languageMatches = in_array($browserLanguageSubstr, $languageValues) && $browserLanguage !== "*";

                        if (!$languageMatches) {
                            $browserLanguageSubstr = substr($browserLanguageSubstr, 0, 2);
                            $languageMatches = in_array($browserLanguageSubstr, $languageValues);

                            if (!$languageMatches) {
                                $languageValues = array_map(function ($languageValue) {
                                    return substr($languageValue, 0, 2);
                                }, $languageValues);

                                $languageMatches = in_array($browserLanguageSubstr, $languageValues);
                            }
                        }

                        $rulesGroup[] = $languageMatches;
                    }
                }

                ////////////////////////////////////////
            }

            $grouped = array_unique($rulesGroup);
            if (!empty($grouped)) { // inclusion exclusion checkbox is checked but non of the cases were enabled
                if (count($grouped) == 1) {
                    $doRedirect = $grouped[0];
                } else {
                    $doRedirect = false;
                }
            }
        }
        return $doRedirect;
    }

    private function getProtocol() {
        $protocol = "http://";
        $isHttpsOn = !empty($_SERVER["HTTPS"]) && (strtolower($_SERVER["HTTPS"]) == "on" || $_SERVER["HTTPS"] == 1);
        $isForwarded = !empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "https");
        if ($isHttpsOn || $isForwarded) {
            $protocol = "https://";
        }
        return $protocol;
    }

    public static function unescapeData($data) {
        $data = is_array($data) ? array_map(["IRRPHelper", "unescapeData"], $data) : stripslashes($data);
        return $data;
    }

    public static function sanitizeData($data) {
        if (is_array($data)) {
            $data = array_map(["IRRPHelper", "sanitizeData"], $data);
        } else {
            if (is_string($data)) {
                $data = trim(sanitize_text_field($data));
            }
        }

        return $data;
    }

    public static function isEmpty($data) {
        $isEmpty = false;

        array_walk_recursive($data, function ($item) use (&$isEmpty) {

            if (is_string($item)) {
                $item = trim($item);
            }

            if (empty($item)) {
                $isEmpty = true;
                return;
            }
        });

        return $isEmpty;
    }

    public static function getRealIPAddr() {
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {   //check ip from share internet
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {   //to check ip is pass from proxy
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    public static function getCombinations($slug) {
        $parts = [];
        if (!empty($slug)) {
            $parts[] = $slug;
            $words = array_filter(explode("-", $slug));
            while (($pos = strrpos($slug, "-")) !== false) {
                $slug = substr($slug, 0, $pos);
                if (!empty(trim($slug, "-"))) {
                    $parts[] = $slug;
                }
            }

            foreach ($words as $word) {
                $parts[] = $word;
            }

            $parts = array_filter($parts);
        }
        return $parts;
    }

    public static function getMicrotime() {
        list($pfx_usec, $pfx_sec) = explode(" ", microtime());
        return ((float) $pfx_usec + (float) $pfx_sec);
    }

    public function getHttpCode($url) {
        if (!empty($url) && function_exists('curl_version')) {
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            return (int) $httpCode;
        }
        return null;
    }

    public function is404($url) {
        $code = $this->getHttpCode($url);
        return $code === 404 ? true : false;
    }

    /**
     * get the per page number for certain items
     */
    public function getItemsPerPage($type) {
        $perPage = 10;
        if ($type === "redirection") {
            $perPage = ($pp = ((int) apply_filters("irrp_redirections_per_page", self::PER_PAGE_REDIRECTIONS))) > 0 ? $pp : self::PER_PAGE_REDIRECTIONS;
        } else if ($type === "log") {
            $perPage = ($pp = ((int) apply_filters("irrp_logs_per_page", self::PER_PAGE_LOGS))) > 0 ? $pp : self::PER_PAGE_LOGS;
        }
        return $perPage;
    }

    public static function getNonce() {
        return wp_create_nonce(self::nonceKey());
    }

    public static function nonceKey() {
        return md5(ABSPATH . get_home_url());
    }

    /*
    *
    * Replaces double slashes (if any) with single slash 
    *
    */    
    public static function removeDoubleSlashes($url){
        $pattern = "/(?<!http:|https:)\/\//";
        $replacement = "/";
        $_url = preg_replace($pattern, $replacement, $url);
        return $_url;
    }   

    public static function isLoggingOut() {
        return isset($_GET["action"]) && 
            $_GET["action"] === "logout" &&
            !isset($_GET["do_redirect"]);
    }

    public function loggedOutRedirectionUrl($redirect_to,$request,$user){
        $refererUrl = wp_get_referer();
        $wpLoginURL = untrailingslashit(wp_login_url());
        if (strpos($refererUrl, $wpLoginURL . '/?action=logout') === false) { // prevent redirect loop if showing confirmation message
            $redirect_to = $refererUrl;
        }
        return $redirect_to;
    }
    public function loggedInRedirectionUrl($redirect_to, $request, $user){
        if (is_a($user, 'WP_User')) {
            if($user->has_cap('administrator') || $user->has_cap('redirect_redirection_admin') || $user->has_cap('manage_options')){
                if($this->dbManager->hasRefererUrl($user->ID)){
                    $redirect_to = $this->dbManager->popRefererUrl($user->ID);
                }
            }
        }
        return $redirect_to;
    }

    public function isExcludedUrl($url) {
        $wpAdminURL = untrailingslashit(admin_url());
        $wpLoginURL = untrailingslashit(wp_login_url());

        $url = strtolower($url);
        $url = str_replace("%2f", "/", $url);
        
        return (strpos($url, $wpAdminURL) !== false) || (strpos($url, $wpLoginURL) !== false);
    }

    public function manualRtrim($string, $charlist) {
        if (strpos($string, $charlist) !== false) {
            $string = substr($string, 0, strrpos($string, $charlist));
            return $this->manualRtrim($string, $charlist);
        }
        return $string;
    }
}
