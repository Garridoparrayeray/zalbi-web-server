<?php

if (!defined("ABSPATH")) {
    exit();
}

interface IRRPConstants {
    
    const PAGE_SETTINGS                     = "irrp-redirection";    
    const OPTIONS_MAIN                      = "irrp_redirection";    
    const OPTIONS_CRON_LOG_DELETE           = "irrp_cron_log_delete";    
    const OPTIONS_LOGS_STATUS               = "irrp_logs_status"; 
    const OPTIONS_AUTO_REDIRECTS            = "irrp_auto_redirects";
    const PER_PAGE_REDIRECTIONS             = 30;
    const PER_PAGE_LOGS                     = 100;
    
    // TABLE NAMES
    const TBL_REDIRECTIONS                  = "irrp_redirections";
    const TBL_REDIRECTION_META              = "irrp_redirectionmeta";
    const TBL_REDIRECTION_LOGS              = "irrp_redirection_logs";
    const TBL_REFERER_URLS                  = "irrp_referer_urls";
    
    // REDIRECTION TYPES
    const TYPE_REDIRECTION                  = "redirection";
    const TYPE_REDIRECTION_RULE             = "redirection_rule";
    
    // META KEYS
    const META_KEY_CRITERIAS                = "criterias";
    const META_KEY_ACTION                   = "action";
    const META_KEY_TABLES_CREATED           = "irrp_tables_created";
    const META_KEY_URL_LENGTH_UPDATED       = "irrp_url_length_updated";
    
    // LOG CODES
    const LOGCODE_IS_404_NO_REDIRECT       = "is_404_no_redirect";
    const LOGCODE_IS_404_REDIRECT_FIXED    = "is_404_redirect_fixed";
    const LOGCODE_IS_404_REDIRECT          = "is_404_redirect";
    const LOGCODE_IS_NOT_404_REDIRECT      = "is_not_404_redirect";
    
}