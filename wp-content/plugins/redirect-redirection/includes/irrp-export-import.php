<?php

if (!defined("ABSPATH")) {
    exit();
}

class IRRPExportImport implements IRRPConstants {

    /**
     * @var IRRPDBManager
     */
    private $dbManager;

    /**
     * @var IRRPHelper
     */
    private $helper;

    public function __construct($dbManager, $helper) {
        $this->dbManager = $dbManager;
        $this->helper = $helper;

        add_action("admin_post_irrp_export", [&$this, "exportRedirects"]);
        add_action("wp_ajax_irrp_import", [&$this, "importRedirects"]);
    }

    public function exportRedirects() {

        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $nonce = empty($_GET["_irrp_nonce"]) ? false : trim(sanitize_text_field($_GET["_irrp_nonce"]));
        $type = empty($_GET["_type"]) ? false : trim(sanitize_text_field($_GET["_type"]));

        if (!$nonce || !$type || !in_array($type, [self::TYPE_REDIRECTION, self::TYPE_REDIRECTION_RULE])) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $homeUrl = get_home_url();

        $action = md5(ABSPATH . $homeUrl);
        if (!wp_verify_nonce($nonce, $action)) {
            die(__("Stop doing this!", "redirect-redirection"));
        }

        $args = [
            "limit" => -1,
            "orderby" => "id",
            "order" => "asc",
            "where" => [
                "condition" => "AND",
                "clauses" => [
                    ["column" => "type", "value" => $type, "compare" => "="]
                ],
            ]
        ];

        $redirects = $this->dbManager->getAll($args);

        if (empty($redirects) || !is_array($redirects)) {
            return;
        }

        $json = [];
        foreach ($redirects as $redirect) {
            $json[] = ["redirect" => $redirect, "metas" => $this->dbManager->getMeta($redirect["id"])];
        }

        if (empty($json)) {
            return;
        }

        $filename = ($type === self::TYPE_REDIRECTION) ? "specific-url-redirections--" : "redirection-rules--";
        $filename .= str_replace(["http://", "https://"], "", $homeUrl) . ".json";

        header("Content-disposition: attachment; filename=$filename");
        header("Content-type: application/json");
        echo json_encode($json, JSON_UNESCAPED_SLASHES);
    }

    public function importRedirects() {

        $response = ["message" => ""];

        if (!current_user_can("manage_options") && !current_user_can("redirect_redirection_admin")) {
            $response["message"] = __("Stop doing this!", "redirect-redirection");
            wp_send_json_error($response);
        }

        $nonce = empty($_POST["_irrp_nonce"]) ? false : trim(sanitize_text_field($_POST["_irrp_nonce"]));
        $redirectionType = empty($_POST["redirectionType"]) ? self::TYPE_REDIRECTION : trim(sanitize_text_field($_POST["redirectionType"]));

        if (!$nonce) {
            $response["message"] = __("Stop doing this!", "redirect-redirection");
            wp_send_json_error($response);
        }

        $action = md5(ABSPATH . get_home_url());
        if (!wp_verify_nonce($nonce, $action)) {
            die(__("Stop doing this!", "redirect-redirection"));
        }



        if (empty($_FILES["_file"]["tmp_name"])) {
            $response["message"] = __("Select import file, please!", "redirect-redirection");
            wp_send_json_error($response);
        }

        if (!empty($_FILES["_file"]["error"])) {
            $response["message"] = __("Unknown error occured!", "redirect-redirection");
            wp_send_json_error($response);
        }

        $importFile = $_FILES["_file"];

        $fileName = $importFile["name"];
        $type = $importFile["type"];
        $tmpName = $importFile["tmp_name"];

        $fileinfo = pathinfo($fileName);

        if ($type !== "application/json" && $fileinfo["extension"] !== "json") {
            $response["message"] = __("File type error! Only JSON files allowed!", "redirect-redirection");
            wp_send_json_error($response);
        }

        $content = file_get_contents($tmpName);
        $json = json_decode($content, true);

        if (empty($json) || !is_array($json)) {
            $response["message"] = __("Invalid file data", "redirect-redirection");
            wp_send_json_error($response);
        }

        foreach ($json as $item) {

            if (empty($item["redirect"]) || !is_array($item["redirect"]) || empty($item["metas"]) || !is_array($item["metas"])) {
                continue;
            }

            $redirect = $item["redirect"];
            $metas = $item["metas"];

            $fromValue = trim(sanitize_text_field($redirect["from"]));
            $type = trim(sanitize_text_field($redirect["type"]));

            if ($type === self::TYPE_REDIRECTION_RULE) {

                if (empty($metas[self::META_KEY_CRITERIAS][0])) {
                    continue;
                }

                $fromValue = $metas[self::META_KEY_CRITERIAS][0]["value"];
            }


            $redirectId = (int) $this->dbManager->isRedirectExists($fromValue, $type);

            $data = [
                "from" => sanitize_text_field($redirect["from"]),
                "match" => sanitize_text_field($redirect["match"]),
                "to" => sanitize_text_field($redirect["to"]),
                "status" => (int) $redirect["status"],
                "timestamp" => (int) $redirect["timestamp"],
                "type" => sanitize_text_field($redirect["type"]),
            ];

            if (empty($redirectId)) { // redirect does not exist insert a new one
                $redirectId = (int) $this->dbManager->add($data);
            } else { // redirect exists update
                $format = ["%s", "%s", "%s", "%d", "%d", "%s"];
                $this->dbManager->edit($redirectId, $data, $format);
            }

            if (!$redirectId) {
                continue;
            }


            foreach ($metas as $key => $value) {

                if (empty($key)) {
                    continue;
                }

                $meta = [
                    "redirect_id" => $redirectId,
                    "meta_key" => $key,
                    "meta_value" => is_array($value) ? maybe_serialize($value) : $value,
                ];

                $this->dbManager->addMeta($meta);
            }
        }

        $selected = [];
        $args = ["type" => $redirectionType];

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
        $response["type"] = $redirectionType;

        $response["message"] = __("Imported successfully", "redirect-redirection");
        wp_send_json_success($response);
    }

}
