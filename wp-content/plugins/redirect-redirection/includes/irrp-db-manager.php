<?php

if (!defined("ABSPATH")) {
    exit();
}

class IRRPDBManager implements IRRPConstants {

    private $tblRedirects;
    private $tblRedirectMeta;
    private $tblRedirectLogs;
    private $tblRefererUrls;

    public function __construct() {
        global $wpdb;
        $this->tblRedirects = $wpdb->prefix . self::TBL_REDIRECTIONS;
        $this->tblRedirectMeta = $wpdb->prefix . self::TBL_REDIRECTION_META;
        $this->tblRedirectLogs = $wpdb->prefix . self::TBL_REDIRECTION_LOGS;
        $this->tblRefererUrls = $wpdb->prefix . self::TBL_REFERER_URLS;

        // Run checks only when initialised.
        $this->checkTablesCreated();
        $this->checkURLLengthUpdated();
    }

    /**
     * Checks if the tables are created.
     */
    public function checkTablesCreated() {
        if ('yes' === get_option(self::META_KEY_TABLES_CREATED, '')) { // Table is already created.
            return;
        }
        // Create the tables.
        $this->createTables(is_multisite());
    }

    /**
     * Checks if the URL length is updated.
     * @since 1.2.6
     */
    public function checkURLLengthUpdated() {
        if ('yes' === get_option(self::META_KEY_URL_LENGTH_UPDATED, '')) { // URL length is already updated.
            return;
        }
        // Update the URL length.
        $this->updateURLLength();
    }

    /**
     * Update the URL length from 255 to 1000.
     * @since 1.2.6
     */
    public function updateURLLength() {
        global $wpdb;
        $table = esc_sql($this->tblRedirects);
        $query = "
            ALTER TABLE `$table`
            MODIFY `from` VARCHAR(1000) DEFAULT NULL,
            MODIFY `to` VARCHAR(1000) NOT NULL
        ";
        $wpdb->query($query);
        update_option(self::META_KEY_URL_LENGTH_UPDATED, 'yes', false);
    }

    /**
     * creates tables for redirections when multisite
     */
    public function createTables($networkWide) {
        global $wpdb;
        if (is_multisite() && $networkWide) {
            $blogIds = $wpdb->get_col("SELECT `blog_id` FROM {$wpdb->blogs}");
            foreach ($blogIds as $blogId) {
                switch_to_blog($blogId);
                $this->_createTables();
                restore_current_blog();
            }
        } else {
            $this->_createTables();
        }
        // Let us easily know when the table is created.
        update_option(self::META_KEY_TABLES_CREATED, 'yes', false);
    }

    private function _createTables() {
        global $wpdb;
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        $engine = version_compare($wpdb->db_version(), "5.6.4", ">=") ? "InnoDB" : "MyISAM";

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tblRedirects}`(
                    `id` INT NOT NULL AUTO_INCREMENT, 
                    `from` VARCHAR(250) DEFAULT NULL, 
                    `match` VARCHAR(250) DEFAULT NULL, 
                    `to` VARCHAR(250) NOT NULL, 
                    `status` TINYINT(1) DEFAULT 1, 
                    `timestamp` BIGINT(11) NOT NULL, 
                    `type` VARCHAR(25) DEFAULT '" . self::TYPE_REDIRECTION . "', 
                PRIMARY KEY (`id`), 
                KEY `from` (`from`), 
                KEY `match` (`match`), 
                KEY `status` (`status`), 
                KEY `timestamp` (`timestamp`), 
                KEY `type` (`type`)) 
                ENGINE=$engine DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate};";
        maybe_create_table($this->tblRedirects, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS  `{$this->tblRedirectMeta}`(
                    `meta_id` INT NOT NULL AUTO_INCREMENT,
                    `redirect_id` INT NOT NULL, 
                    `meta_key` VARCHAR(250) NOT NULL, 
                    `meta_value` TEXT DEFAULT NULL, 
                PRIMARY KEY (`meta_id`), 
                KEY `redirect_id` (`redirect_id`), 
                KEY `meta_key` (`meta_key`)) 
                ENGINE=$engine DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate};";
        maybe_create_table($this->tblRedirectMeta, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tblRedirectLogs}` (
                    `id` INT NOT NULL AUTO_INCREMENT, 
                    `redirect_id` INT NOT NULL,
                    `request_url` text NOT NULL, 
                    `response_url` text NOT NULL, 
                    `request_date` datetime NOT NULL, 
                    `request_timestamp` INT NOT NULL, 
                    `request_code` SMALLINT(6) NOT NULL, 
                    `response_code` SMALLINT(6) NOT NULL, 
                    `log_code` VARCHAR(100) NOT NULL, 
                    `extras` longtext DEFAULT NULL, 
                PRIMARY KEY (`id`), 
                KEY `redirect_id` (`redirect_id`), 
                KEY `request_timestamp` (`request_timestamp`), 
                KEY `request_code` (`request_code`),
                KEY `response_code` (`response_code`),
                KEY `log_code` (`log_code`)) 
                ENGINE=$engine DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
        maybe_create_table($this->tblRedirectLogs, $sql);


        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tblRefererUrls}` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `url` TEXT NOT NULL,
            `timestamp` INT NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `timestamp` (`timestamp`))
            ENGINE=$engine DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate};";
        $created = maybe_create_table($this->tblRefererUrls, $sql);

        // Add count column to redirection logs table
        $sql = "ALTER TABLE `{$this->tblRedirectLogs}` ADD `count` INT DEFAULT 1 AFTER `log_code`;";
        maybe_add_column($this->tblRedirectLogs, "count", $sql);
    }

    public function onNewBlog($blogId, $userId, $domain, $path, $siteId, $meta) {
        if (is_plugin_active_for_network(IRRP_DIR_NAME . "/redirect-redirection.php")) {
            switch_to_blog($blogId);
            $this->_createTables();
            restore_current_blog();
        }
    }

    public function onDeleteBlog($tables) {
        global $wpdb;
        $tables[] = $wpdb->prefix . self::TBL_REDIRECTIONS;
        $tables[] = $wpdb->prefix . self::TBL_REDIRECTION_META;
        return $tables;
    }

    /**
     * get the last redirect id by type
     */
    public function getLastId($args = []) {
        global $wpdb;

        $defaults = []; // empty for now...
        $parsed = array_replace_recursive($defaults, $args);

        $sql = "SELECT MAX(`redirect_id`) FROM `{$this->tblRedirects}`";
        if (!empty($parsed["type"]) && ($type = trim($parsed["type"]))) {
            $sql .= " WHERE `type` = %s";
            $sql = $wpdb->prepare($sql, $type);
        }
        return (int) $wpdb->get_var($sql);
    }

    /**
     * add new redirect
     */
    public function add($data) {
        global $wpdb;
        $wpdb->insert($this->tblRedirects, $data, ["%s", "%s", "%s", "%d", "%d", "%s"]);
        return (int) $wpdb->insert_id;
    }

    /**
     * add new redirect meta
     */
    public function addMeta($data) {
        global $wpdb;
        $wpdb->insert($this->tblRedirectMeta, $data, ["%d", "%s", "%s"]);
        return (int) $wpdb->insert_id;
    }

    /**
     * update redirect meta
     */
    public function updateMeta($id, $metaKey, $data) {
        global $wpdb;
        $result = $wpdb->update($this->tblRedirectMeta, $data, ["redirect_id" => $id, "meta_key" => $metaKey]);
        return $result !== false;
    }

    /**
     * get redirect single or all metadata
     */
    public function getMeta($id, $metaKey = "") {
        global $wpdb;
        if ($metaKey) {
            $sql = $wpdb->prepare("SELECT `meta_value` FROM `$this->tblRedirectMeta` WHERE `redirect_id` = %d AND `meta_key` = %s LIMIT 1;", $id, $metaKey);
            return maybe_unserialize($wpdb->get_var($sql));
        } else {
            $sql = $wpdb->prepare("SELECT `meta_key`, `meta_value` FROM `$this->tblRedirectMeta` WHERE `redirect_id` = %d;", $id);
            $metadata = $wpdb->get_results($sql, ARRAY_A);
            $loadedData = [];
            if ($metadata && is_array($metadata)) {
                foreach ($metadata as $data) {
                    $key = trim($data["meta_key"]);
                    $value = maybe_unserialize($data["meta_value"]);
                    $loadedData[$key] = $value;
                }
            }
            return $loadedData;
        }
    }

    /**
     * edit redirect by id
     */
    public function edit($id, $data, $dataFormat) {
        global $wpdb;
        $result = $wpdb->update($this->tblRedirects, $data, ["id" => $id], $dataFormat, ["%d"]);
        return $result !== false;
    }

    public function statusBulkEdit($ids, $status = 1) {
        global $wpdb;
        if (!empty($ids) && is_array($ids)) {
            $idsStr = esc_sql(implode(",", array_map("intval", $ids)));
            $status = (int) $status;
            $sql = $wpdb->prepare("UPDATE `$this->tblRedirects` SET `status` = %d WHERE `id` IN($idsStr)", $status);
            return $wpdb->query($sql);
        }
        return false;
    }

    /**
     * delete redirect by id
     */
    public function delete($id) {
        global $wpdb;
        return $wpdb->delete($this->tblRedirects, ["id" => $id], ["%d"]);
    }

    /**
     * delete redirect meta by id
     */
    public function deleteMeta($id) {
        global $wpdb;
        return $wpdb->delete($this->tblRedirectMeta, ["redirect_id" => $id], ["%d"]);
    }

    public function bulkDelete($ids) {
        global $wpdb;
        if (!empty($ids) && is_array($ids)) {
            $idsStr = esc_sql(implode(",", array_map("intval", $ids)));

            $args = [
                "fields" => ["id"],
                "limit" => -1,
                "where" => [
                    "condition" => "AND",
                    "clauses" => [
                        ["column" => "id", "value" => $ids, "compare" => "IN"]
                    ],
                ]
            ];
            $redirectIds = $this->getAll($args);

            $sql = "DELETE FROM `$this->tblRedirects` WHERE `id` IN($idsStr)";
            $result = $wpdb->query($sql);
            if ($result !== false) {
                $this->bulkDeleteMeta($redirectIds);
            }
            return $result;
        }
        return false;
    }

    public function bulkDeleteMeta($ids) {
        global $wpdb;
        if (!empty($ids) && is_array($ids)) {
            $idsStr = esc_sql(implode(",", array_map("intval", $ids)));
            $sql = "DELETE FROM `$this->tblRedirectMeta` WHERE `redirect_id` IN($idsStr);";
            $result = $wpdb->query($sql);
            return $result === false;
        }
        return false;
    }

    /**
     * get a redirect by id
     */
    public function get($id) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM `{$this->tblRedirects}` WHERE `id` = %d;", $id);
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * get count of all redirects
     */
    public function getCount($args = []) {
        global $wpdb;

        $defaults = []; // empty for now...
        $parsed = array_replace_recursive($defaults, $args);

        $sql = "SELECT COUNT(*) FROM `{$this->tblRedirects}`";
        if (!empty($parsed["type"]) && ($type = trim($parsed["type"]))) {
            $sql .= " WHERE `type` = %s";
            $sql = $wpdb->prepare($sql, $type);
        }
        return $wpdb->get_var($sql);
    }

    /**
     * get a redirect by redirect id
     */
    public function getAll($args = []) {
        global $wpdb;
        $defaults = [
            "limit" => self::PER_PAGE_REDIRECTIONS,
            "offset" => 0,
            "orderby" => "id",
            "order" => "desc",
            "fields" => "",
        ];

        $parsed = array_replace_recursive($defaults, $args);

        $sql = "SELECT";

        if (!empty($parsed["fields"]) && is_array($parsed["fields"])) {
            $fields = implode(",", array_map("esc_sql", $parsed["fields"]));
            $sql .= " $fields";
        } else {
            $sql .= " *";
        }

        $sql .= " FROM `$this->tblRedirects`";

        if (!empty($parsed["where"]["clauses"]) && is_array($parsed["where"]["clauses"]) && !empty($parsed["where"]["condition"])) {
            $sqlWhere = " WHERE";
            $sqlConditions = "";
            $countClauses = count($parsed["where"]["clauses"]);
            $whereCondition = strtoupper($parsed["where"]["condition"]);
            $condition = ($whereCondition === "AND") || ($whereCondition === "OR") ? $whereCondition : "AND";
            if ($countClauses > 1) {
                foreach ($parsed["where"]["clauses"] as $clause) {
                    if (!empty($clause["column"]) && !empty($clause["value"]) && !empty($clause["compare"])) {
                        if ((strtoupper($clause["compare"]) === "IN" || strtolower($clause["compare"]) === "in") && is_array($clause["value"])) {
                            $valueStr = '("' . implode('", "', array_map("esc_sql", $clause["value"])) . '")';
                            $sqlConditions .= " `" . esc_sql($clause["column"]) . "`" . esc_sql($clause["compare"]) . $valueStr . " " . $condition;
                        } else {
                            $sqlConditions .= " `" . esc_sql($clause["column"]) . "`" . esc_sql($clause["compare"]) . "'" . esc_sql($clause["value"]) . "'" . " " . $condition;
                        }
                    }
                }
            } else {
                $clause = $parsed["where"]["clauses"][0];
                if (!empty($clause["column"]) && !empty($clause["value"]) && !empty($clause["compare"])) {
                    if ((strtoupper($clause["compare"]) === "IN" || strtolower($clause["compare"]) === "in") && is_array($clause["value"])) {
                        $valueStr = '("' . implode('", "', array_map("esc_sql", $clause["value"])) . '")';
                        $sqlConditions .= " `" . esc_sql($clause["column"]) . "`" . esc_sql($clause["compare"]) . $valueStr;
                    } else {
                        $sqlConditions .= " `" . esc_sql($clause["column"]) . "`" . esc_sql($clause["compare"]) . "'" . esc_sql($clause["value"]) . "'";
                    }
                }
            }

            $sqlConditions = rtrim($sqlConditions, $condition);

            $sql .= $sqlConditions ? $sqlWhere . $sqlConditions : "";
        }

        $sql .= " ORDER BY `" . esc_sql($parsed["orderby"]) . "` " . esc_sql($parsed["order"]);

        if (!empty($parsed["limit"]) && $parsed["limit"] > 0) {
            $sql .= " LIMIT " . (int) $parsed["limit"];

            if (!empty($parsed["offset"])) {
                $sql .= " OFFSET " . (int) $parsed["offset"];
            }
        }

        if (!empty($parsed["fields"]) && is_array($parsed["fields"]) && count($parsed["fields"]) === 1) {
            return $wpdb->get_col($sql);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function search($search, $args = []) {
        global $wpdb;
        $s = trim($search);
        $searchIsNotEmpty = strlen($s);
        if ($searchIsNotEmpty) {
            $defaults = [
                "limit" => self::PER_PAGE_REDIRECTIONS,
                "offset" => 0,
                "orderby" => "id",
                "order" => "desc",
                "fields" => "",
            ];

            $parsed = array_merge($defaults, $args);

            $sql = "SELECT";

            if (!empty($parsed["fields"]) && is_array($parsed["fields"])) {
                $fields = implode(",", array_map("esc_sql", $parsed["fields"]));
                $sql .= " $fields";
            } else {
                $sql .= " *";
            }

            $redirectionType = !empty($parsed["type"]) && ($type = trim($parsed["type"])) ? $type : self::TYPE_REDIRECTION;

            $sql .= " FROM `$this->tblRedirects`";

            if ($redirectionType === self::TYPE_REDIRECTION) {
                $sql .= " WHERE (`from` LIKE '%" . esc_sql($s) . "%' OR `to` LIKE '%" . esc_sql($s) . "%')";
            } else {
                $sql .= " INNER JOIN `$this->tblRedirectMeta` ON `id` = `redirect_id`";
                $sql .= " WHERE (`to` LIKE '%" . esc_sql($s) . "%' OR `meta_value` REGEXP '\"value\";s\:[0-9]+\:\"[^\"]*(" . esc_sql($s) . ")+[^\"]*\"')";
                $sql .= " AND `meta_key` = '" . self::META_KEY_CRITERIAS . "'";
            }

            $sql .= " AND `type` = '" . esc_sql($redirectionType) . "'";

            $sql .= " ORDER BY " . esc_sql($parsed["orderby"]) . " " . esc_sql($parsed["order"]);

            if (!empty($parsed["limit"])) {
                $sql .= " LIMIT " . (int) $parsed["limit"];

                if (!empty($parsed["offset"])) {
                    $sql .= " OFFSET " . (int) $parsed["offset"];
                }
            }

            if (!empty($parsed["fields"]) && is_array($parsed["fields"]) && count($parsed["fields"]) === 1) {
                return $wpdb->get_col($sql);
            }

            return $wpdb->get_results($sql, ARRAY_A);
        }
        return [];
    }

    public function searchCount($search, $args = []) {
        global $wpdb;

        $defaults = []; // empty for now...
        $parsed = array_merge($defaults, $args);

        $s = trim($search);
        $searchIsNotEmpty = strlen($s);

        if ($searchIsNotEmpty) {

            $redirectionType = !empty($parsed["type"]) && ($type = trim($parsed["type"])) ? $type : self::TYPE_REDIRECTION;

            $sql = "SELECT COUNT(*) FROM `{$this->tblRedirects}`";

            if ($redirectionType === self::TYPE_REDIRECTION) {
                $sql .= " WHERE `from` LIKE '%" . esc_sql($s) . "%' OR `to` LIKE '%" . esc_sql($s) . "%'";
            } else {
                $sql .= " INNER JOIN `$this->tblRedirectMeta` ON `id` = `redirect_id`";
                $sql .= " WHERE (`to` LIKE '%" . esc_sql($s) . "%' OR `meta_value` REGEXP '\"value\";s\:[0-9]+\:\"[^\"]*(" . esc_sql($s) . ")+[^\"]*\"')";
                $sql .= " AND `meta_key` = '" . self::META_KEY_CRITERIAS . "'";
            }

            $sql .= " AND `type` = '" . esc_sql($type) . "'";

            return $wpdb->get_var($sql);
        }
        return 0;
    }

    /**
     * get a redirect by redirect "from" param
     */
    public function getMatched($from, $status = 1) {
        global $wpdb;
        $from = function_exists("mb_strtolower") ? mb_strtolower(rtrim($from, "/")) : strtolower(rtrim($from, "/"));
        $sql = "SELECT * FROM `{$this->tblRedirects}` WHERE (LOWER(`match`) LIKE LOWER('" . esc_sql($from) . "') OR LOWER(`match`) LIKE LOWER('" . esc_sql($from . "/") . "'))";
        if (trim($status) !== "") {
            $sql .= " AND `status` = '" . (int) $status . "'";
        }
        $sql .= " AND `type` = '" . self::TYPE_REDIRECTION . "'";
        $sql .= " ORDER BY `timestamp` DESC, `id` DESC LIMIT 1;";
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * get a redirect rules which not contains and not starts with ...
     */
    public function getRules($status = 1) {
        global $wpdb;
        $sql = "SELECT * FROM `{$this->tblRedirects}` WHERE `type` = '" . self::TYPE_REDIRECTION_RULE . "'";
        if (trim($status) !== "") {
            $sql .= " AND `status` = " . (int) $status;
        }
        $sql .= " ORDER BY `timestamp` DESC, `id` DESC;";
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * gets post id from the db by the slug and type from the requested URL
     */
    public function getBestMatchedIDBySlug($slug, $type = "") {
        if (!empty($slug)) {
            global $wpdb;

            $sql = "SELECT `ID` FROM `$wpdb->posts` WHERE `post_status` = 'publish' ";
            if (!empty($type)) {
                $sql .= $wpdb->prepare("AND `post_type` = %s", $type);
            }
            $possibleSlugs = array_unique(IRRPHelper::getCombinations($slug));
            if (!empty($possibleSlugs)) {
                $sql .= " ORDER BY ";
                foreach ($possibleSlugs as $possibleSlug) {
                    $sql .= "`post_name` LIKE '%" . $wpdb->esc_like($possibleSlug) . "%' DESC,";
                }
                $sql = rtrim($sql, ",") . " LIMIT 1;";
                $result = array_filter($wpdb->get_col($sql));
                if (!empty($result)) {
                    return $result[0];
                }
            }
        }
        return 0;
    }

    /**
     * check if 'are-404s' rule already exists in the db and enabled
     */
    public function isAre404sRuleExists($redirct_rules = []) {
	    $rules = !empty($redirct_rules) && is_array($redirct_rules) ? $redirct_rules : $this->getRules();

        if (empty($rules) || !is_array($rules)) {
            return false;
        }

        foreach ($rules as $rule) {
            $criterias = $this->getMeta($rule["id"], "criterias");
            if (!empty($criterias) && is_array($criterias)) {
                foreach ($criterias as $criteria) {
                    if (isset($criteria["criteria"]) && $criteria["criteria"] === "are-404s" && ((int) $rule["status"] === 1)) {
                        return $rule["id"];
                    }
                }
            }
        }
        return false;
    }

	/**
	 * check if 'all-urls' rule already exists in the db and enabled
	 */
	public function isAllURLsRuleExists($redirct_rules = []) {
		$rules = !empty($redirct_rules) && is_array($redirct_rules) ? $redirct_rules : $this->getRules();

		if (empty($rules) || !is_array($rules)) {
			return false;
		}

		foreach ($rules as $rule) {
			$criterias = $this->getMeta($rule["id"], "criterias");
			if (!empty($criterias) && is_array($criterias)) {
				foreach ($criterias as $criteria) {
					if (isset($criteria["criteria"]) && $criteria["criteria"] === "all-urls" && ((int) $rule["status"] === 1)) {
						return $rule["id"];
					}
				}
			}
		}
		return false;
	}

    public function isRuleType($type, $ruleId){
        $criterias = $this->getMeta($ruleId, "criterias");
        if (!empty($criterias) && is_array($criterias)) {
            foreach ($criterias as $criteria) {
                if (isset($criteria["criteria"]) && $criteria["criteria"] === $type) {
                    return true;
                }
            }
        }
    }

    public function getPostBySlug($slug, $year = "", $month = "", $day = "") {
        global $wpdb;
        $id = false;
        if ($s = trim($slug)) {
            $where = $wpdb->prepare("post_name = %s", $s);

            if ($y = ((int) $year)) {
                $where .= $wpdb->prepare(" AND YEAR(post_date) = %d", $y);
            }

            if ($m = ((int) $month)) {
                $where .= $wpdb->prepare(" AND MONTH(post_date) = %d", $m);
            }

            if ($d = ((int) $day)) {
                $where .= $wpdb->prepare(" AND DAYOFMONTH(post_date) = %d", $d);
            }

            $id = $wpdb->get_var("SELECT `ID` FROM `$wpdb->posts` WHERE $where AND `post_status` = 'publish';");
        }
        return $id;
    }

    /* check if redirection exists >>> for importing */

    public function isRedirectExists($fromValue, $type = self::TYPE_REDIRECTION) {
        global $wpdb;

        if (empty($fromValue) || empty($type)) {
            return false;
        }


        if ($type === self::TYPE_REDIRECTION) {
            $sql = "SELECT `id` FROM `$this->tblRedirects` 
                    WHERE `from` = '" . esc_sql($fromValue) . "' AND 
                          `match` = '/" . esc_sql(basename($fromValue)) . "' AND 
                          `type` = '" . self::TYPE_REDIRECTION . "' 
                          ORDER BY `id` DESC LIMIT 1;";
        } else {
            $sql = "SELECT r.`id` FROM `$this->tblRedirects` AS r
                    INNER JOIN `$this->tblRedirectMeta` AS rm ON r.`id` = rm.`redirect_id`
                    WHERE rm.`meta_key` = '" . self::META_KEY_CRITERIAS . "'
                    AND rm.`meta_value` REGEXP '\"value\";s\:[0-9]+\:\"[^\"]*(" . esc_sql($fromValue) . ")+[^\"]*\"'
                    AND r.`type` = '" . self::TYPE_REDIRECTION_RULE . "'
                    ORDER BY `id` DESC LIMIT 1;";
        }

        return $wpdb->get_var($sql);
    }

    /**
     * **********************************************************
     * ************** REDIRECTION AND 404 LOGS ******************
     * **********************************************************
     * ************************ START ***************************
     */

    /**
     * add a redirection log
     */
    public function logAdd($data) {
        global $wpdb;
        if($data["log_code"] === self::LOGCODE_IS_404_NO_REDIRECT) {
            // Remove query string from request url
            if (strtok($data["request_url"], '?') !== $data["request_url"]){
                $data["request_url"] = strtok($data["request_url"], '?');
                $data["request_url"] = $data["request_url"] . "/";
            }
            
            $requestURL = esc_sql($data["request_url"]);
            $logCode = esc_sql($data["log_code"]);
            $updated = $wpdb->query("UPDATE `" . $this->tblRedirectLogs .  "` SET `count` = count + 1 , `request_timestamp` = UNIX_TIMESTAMP() WHERE `request_url` = '" . $requestURL . "' AND `log_code` = '" . $logCode .  "'");
            if ($updated){
                return (int) $wpdb->insert_id;
            }
        }
        $wpdb->insert($this->tblRedirectLogs, $data, [
            "%d", // redirect_id        (integer)
            "%s", // request_url        (string)
            "%s", // response_url       (string)
            "%s", // request_date       (string)
            "%d", // request_timestamp  (integer)
            "%d", // request_code       (integer)
            "%d", // response_code      (integer)
            "%s", // log_code           (string)
            "%s", // extras             (string => serialized)
        ]);
        return (int) $wpdb->insert_id;
    }

    /**
     * get redirection(s) by args
     */
    public function logGet($queryArgs = []) {
        global $wpdb;

        $logsPerPage = ($lpp = ((int) apply_filters("irrp_logs_per_page", self::PER_PAGE_LOGS))) > 0 ? $lpp : self::PER_PAGE_LOGS;

        $defaults = [
            "id" => 0,
            "redirect_id" => "",
            "request_url" => "",
            "response_url" => "",
            "request_timestamp" => ["timestamp" => 0, "compare" => ""],
            "request_code" => "",
            "response_code" => "",
            "log_code" => "",
            "fields" => [],
            "count" => false,
            "orderby" => ["id"],
            "order" => "desc",
            "limit" => $logsPerPage,
            "offset" => 0
        ];

        $args = wp_parse_args($queryArgs, $defaults);

        if ($args["count"]) {
            $sql = "SELECT COUNT(*) FROM `{$this->tblRedirectLogs}` WHERE 1";
        } else {
            $fields = "*";
            if (!empty($args["fields"]) && is_array($args["fields"])) {
                $fields = implode(",", array_map("esc_sql", $args["fields"]));
            }

            $sql = "SELECT {$fields} FROM `{$this->tblRedirectLogs}` WHERE 1";
        }

        $sql .= $this->whereLogId($args);
        $sql .= $this->whereLogRedirectId($args);
        $sql .= $this->whereLogRequestUrl($args);
        $sql .= $this->whereLogResponseUrl($args);
        $sql .= $this->whereLogRequestTimestamp($args);
        $sql .= $this->whereLogRequestCode($args);
        $sql .= $this->whereLogResponseCode($args);
        $sql .= $this->whereLogLogCode($args);

        $sql .= $this->logOrderBy($args);
        $sql .= $this->logLimitOffset($args);
        return $args["count"] ? (int) $wpdb->get_var($sql) : $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Count specifit redirection usage.
     *
     * @param int $redirect_id Redirection Id.
     * @return int
     */
    public function logCountUsage($redirect_id) {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT Count(id) FROM `{$this->tblRedirectLogs}` WHERE `redirect_id` = {$redirect_id}" );
    }

    /**
     * delete redirection(s) by args
     */
    public function logDelete($args = []) {
        global $wpdb;

        if (empty($args)) {
            $sql = "TRUNCATE `{$this->tblRedirectLogs}`;";
        } else {
            $sql = "DELETE FROM `{$this->tblRedirectLogs}` WHERE 1";

            $sql .= $this->whereLogId($args);
            $sql .= $this->whereLogRedirectId($args);
            $sql .= $this->whereLogRequestUrl($args);
            $sql .= $this->whereLogResponseUrl($args);
            $sql .= $this->whereLogRequestTimestamp($args);
            $sql .= $this->whereLogRequestCode($args);
            $sql .= $this->whereLogResponseCode($args);
            $sql .= $this->whereLogLogCode($args);
        }
        return (int) $wpdb->query($sql);
    }

    private function whereLogId($args) {
        $sql = "";
        if (!empty($args["id"])) {
            $sql .= " AND `id` = " . ((int) $args["id"]);
        }
        return $sql;
    }

    private function whereLogRedirectId($args) {
        $sql = "";
        if (!empty($args["redirect_id"])) {
            $sql .= " AND `redirect_id` = " . ((int) $args["redirect_id"]);
        }
        return $sql;
    }

    private function whereLogRequestUrl($args) {
        $sql = "";
        if (!empty($args["request_url"])) {
            $sql .= " AND `request_url` = '" . esc_sql($args["request_url"]) . "'";
        }
        return $sql;
    }

    private function whereLogResponseUrl($args) {
        $sql = "";
        if (!empty($args["response_url"])) {
            $sql .= " AND `response_url` = '" . esc_sql($args["response_url"]) . "'";
        }
        return $sql;
    }

    private function whereLogRequestTimestamp($args) {
        $sql = "";
        if (!empty($args["request_timestamp"]["timestamp"]) && !empty($args["request_timestamp"]["compare"])) {
            $sql .= " AND `request_timestamp`" . esc_sql($args["request_timestamp"]["compare"]) . esc_sql($args["request_timestamp"]["timestamp"]);
        }
        return $sql;
    }

    private function whereLogRequestCode($args) {
        $sql = "";
        if (!empty($args["request_code"])) {
            $sql .= " AND `request_code` = " . ((int) $args["request_code"]);
        }
        return $sql;
    }

    private function whereLogResponseCode($args) {
        $sql = "";
        if (!empty($args["response_code"])) {
            $sql .= " AND `response_code` = " . ((int) $args["response_code"]);
        }
        return $sql;
    }

    private function whereLogLogCode($args) {
        $sql = "";
        if (!empty($args["log_code"])) {
            $sql .= " AND `log_code` = '" . esc_sql($args["log_code"]) . "'";
        }
        return $sql;
    }

    private function logOrderBy($args) {
        $sql = "";
        if (empty($args["count"]) && !empty($args["orderby"]) && is_array($args["orderby"]) && !empty($args["order"])) {
            $orderby = "";
            foreach ($args["orderby"] as $field) {
                $orderby .= " ORDER BY `" . esc_sql($field) . "` " . esc_sql($args["order"]) . ",";
            }

            $orderby = rtrim($orderby, ",");
            $sql .= $orderby;
        }
        return $sql;
    }

    private function logLimitOffset($args) {
        $sql = "";
        if (empty($args["count"]) && !empty($args["limit"])) {

            if (($limit = ((int) $args["limit"])) > 0) {
                $sql .= " LIMIT {$limit}";

                if (!empty($args["offset"])) {

                    if (($offset = (int) $args["offset"]) > 0) {
                        $sql .= " OFFSET {$offset}";
                    }
                }
            }
        }
        return $sql;
    }

    /**
     * **********************************************************
     * ************** REDIRECTION AND 404 LOGS ******************
     * **********************************************************
     * ************************* END ****************************
     */

    /**
     * **********************************************************
     * ******************** AUTO REDIRECTS **********************
     * **********************************************************
     * ************************ START ***************************
     */
    public function isLogMeWhereIFinishedEnabled() {
        $option = get_option(self::OPTIONS_AUTO_REDIRECTS, []);
        return isset($option["log_me_where_i_finished"]) && $option["log_me_where_i_finished"] === "1";
    }

    public function popRefererUrl($userId) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT `url` FROM `{$this->tblRefererUrls}` WHERE `user_id` = %d ORDER BY `timestamp` ASC LIMIT 1;", $userId);
        $refererUrl = $wpdb->get_var($sql);
        if ($refererUrl) {
            $this->deleteRefererUrl($userId);
            return $refererUrl;
        }else{
            return null;
        }
    }

    public function addRefererUrl($userId, $url) {
        global $wpdb;
        $oldRefererUrl = $this->hasRefererUrl($userId);
        if ($oldRefererUrl) {
            $wpdb->update($this->tblRefererUrls, ["timestamp" => time(), "url" => $url], ["id" => $oldRefererUrl], ["%d", "%s"], ["%d"]);
            return $oldRefererUrl;
        }
        $wpdb->insert($this->tblRefererUrls, ["user_id" => $userId, "timestamp" => time(), "url" => $url], ["%d", "%d", "%s"]);
        return (int) $wpdb->insert_id;
    }

    public function deleteRefererUrl($userId) {
        global $wpdb;
        return $wpdb->delete($this->tblRefererUrls, ["user_id" => $userId], ["%d"]);
    }

    public function hasRefererUrl($userId) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT `id` FROM `{$this->tblRefererUrls}` WHERE `user_id` = %d LIMIT 1;", $userId);
        
        $x = $wpdb->get_var($sql);
        if (isset($x)) return $x;
        else return false;
    }
    /**
     * **********************************************************
     * ******************** AUTO REDIRECTS **********************
     * **********************************************************
     * ************************* END ****************************
     */

    /**
     * drop tables on blog delete
     */
    public function dropTables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS `$this->tblRedirects`;");
        $wpdb->query("DROP TABLE IF EXISTS `$this->tblRedirectMeta`;");
        $wpdb->query("DROP TABLE IF EXISTS `$this->tblRedirectLogs`;");
        $wpdb->query("DROP TABLE IF EXISTS `$this->tblRefererUrls`;");
    }

}
