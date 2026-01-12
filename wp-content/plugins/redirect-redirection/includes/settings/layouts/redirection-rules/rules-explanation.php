<?php
if (!defined("ABSPATH")) {
    exit();
}

$regexHelp = sprintf(
    '%1$s<a href="%2$s" target="_blank" class="ask-us">%3$s</a>%4$s',
    __("Need help with a RegEx or a Redirection? Ask us "),
    "https://wordpress.org/support/plugin/redirect-redirection/",
    __("here", "redirect-redirection"),
    __("!", "redirect-redirection")
);
?>
<div class="header__popup header__popup--hidden header-popup">
    <div class="header-popup__heading">
        <?php _e( "Redirection to-rules explained​", "redirect-redirection" ); ?>
    </div>
    <div class="header-popup__body">
        <div class="header-popup__row">
            <div class="header-popup__body-col">
                <span class="header-popup__body-label">
                    <?php _e( "A specific URL:", "redirect-redirection" ); ?>
                </span>
            </div>
            <div class="header-popup__body-col">
                <p class="header-popup__paragraph">
                    <?php _e( "Redirects all URLs to the stated URL.", "redirect-redirection" ); ?>
                </p>
            </div>
        </div>

        <div class="header-popup__row">
            <div class="header-popup__body-col">
                <span class="header-popup__body-label">
                    <?php _e( "URLs with new string:", "redirect-redirection" ); ?>
                </span>
                <span class="header-popup__body-label-desc">
                    <?php _e( "(Only shown if “Contain” is selected on the left)", "redirect-redirection" ); ?>
                </span>
            </div>
            <div class="header-popup__body-col">
                <p class="header-popup__paragraph">
                    <?php _e( "Redirects to URLs which contain the string you entered on the right instead ​of the string you
                    entered on the left", "redirect-redirection" ); ?>
                    (“<strong><?php _e( "replace", "redirect-redirection" ); ?></strong>”). ​
                </p>
                <p class="header-popup__paragraph">
                    <?php
                    printf(
                        
                        __( '%1$sExample:%2$s You selected to redirect all URLs which contain the string “old-blog”. If you enter here “new-blog” then a redirect from %3$s to %4$s will be applied for all your pages/posts on the old URLs.', 'redirect-redirection' ),
                        '<strong><em>',
                        '</em></strong>',
                        '<span class="highlighted">https://your-website.com/old-blog/post1</span>',
                        '<span class="highlighted">https://your-website.com/new-blog/post1</span>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="header-popup__row">
            <div class="header-popup__body-col">
                <span class="header-popup__body-label"><?php _e( "URLs with removed string:", "redirect-redirection" ); ?></span>
                <span class="header-popup__body-label-desc"><?php _e( "(Only shown if “Contain” is selected on the left)", "redirect-redirection" ); ?></span>
            </div>
            <div class="header-popup__body-col">
                <p class="header-popup__paragraph"><?php _e( "Redirects to URLs which are identical except they don’t contain the string you entered on the left at all (“&ZeroWidthSpace;<strong>remove</strong>”).", "redirect-redirection" ); ?></p>
                <p class="header-popup__paragraph">
                    <?php
                    printf(
                        
                        __( '%1$sExample:%2$s You selected to redirect all URLs which contain the string “old-blog”, and the current url structure is %3$s. With this option your entered string will be removed so %4$s will redirect to %5$s', 'redirect-redirection' ),
                        '<strong><em>',
                        '</em></strong>',
                        '<span class="highlighted">https://your-website/old-blog/post1</span>',
                        '<span class="highlighted">https://your-website/old-blog/post1</span>',
                        '<span class="highlighted">https://your-website/post1</span>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="header-popup__row">
            <div class="header-popup__body-col">
                <span class="header-popup__body-label">
                    <?php _e( "New permalink structure:", "redirect-redirection" ); ?>
                </span>
                <span class="header-popup__body-label-desc">
                    <?php _e( "(Only shown if “Have Permalink Structure” is selected on the left)", "redirect-redirection" ); ?>
                </span>
            </div>
            <div class="header-popup__body-col">
                <p class="header-popup__paragraph">
                    <?php _e( "Redirects to the new permalink structure of your choice.", "redirect-redirection" ); ?>
                </p>
            </div>
        </div>

        <div class="header-popup__row">
            <div class="header-popup__body-col">
                <span class="header-popup__body-label">
                    <?php _e( "Regex matches:", "redirect-redirection" ); ?>
                </span>
                <span class="header-popup__body-label-desc">
                    <?php _e( "(Only shown if “Regex matches” is selected on the left)", "redirect-redirection" ); ?>
                </span>
            </div>
            <div class="header-popup__body-col">
                <p class="header-popup__paragraph">
                    <?php _e( "Redirects to the new Regex pattern you entered on the right.", "redirect-redirection" ); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="header-popup__footer">
        <div class="regex-help">
            <span class="faq-icon">?</span>
            <?php echo $regexHelp; ?>
        </div>
        <button class="header-popup__close-btn ir-header-popup-close">
            <?php _e( "Close", "redirect-redirection" ); ?>
        </button>
    </div>
</div>