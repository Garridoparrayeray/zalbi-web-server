=== Redirection ===
Contributors: Socialdude
Tags: Redirect, Redirection, 301, 404, Redirects
Requires at least: 4.6
Tested up to: 6.8.1
Stable tag: 1.2.7
License: GPLv3
Requires PHP: 5.6

Redirection

== Description ==

**Try it out on your free dummy site: Click here => [https://tastewp.com/plugins/redirect-redirection](https://demo.tastewp.com/redirection).**
(this trick works for all plugins in the WP repo - just replace "wordpress" with "tastewp" in the URL)

Create specific URL redirections and redirection rules super-easily on a beautiful, user-friendly interface of the Redirection plugin.

Creating even conditional redirections has never been easier and quicker than with the Redirection plugin.

Creating redirection has never been simpler, but for fine-tuning, you can use a wide choice of advanced options:

- Redirect HTTP codes can be 301, 302, 303, 304, 307 and 308;
- Inclusion and exclusion rules: logged in/out users, specific user role, user’s referrer link, user’s agent, user’s cookie, user’s IP, server, and language;
- Redirection rules: Simplified redirection rules as well as advanced RegEx matches - URLs that contain specific string to new or removed string, URLs that start with X, specific permalink structures, RegEx matches, and 404s. Setting redirection rules with several matching conditions is also possible;
- Managing all redirects comfortably - enable/disable, edit and delete redirects neatly listed in the plugin menu;
- ⭐️**NEW!** 404 page redirection to a Specific URL (e.g. homepage) or to a Random Similar Post;
- ⭐️**NEW!** Import/Export redirection lists (both Specific URL Redirections and Redirection Rules);
- ⭐️**NEW!** Redirection & 404 Logs - chronological log of all redirections and 404s with a one-click solution to add the redirection and fix the dead links;
- Automatic redirects, Change URL -  these features are coming soon!

This redirect plugin is part of the Inisev product family. Have a look at our [other projects](https://inisev.com) too please! :)

== Frequently Asked Questions ==

= Can I limit who can create redirections on my site? =

By default, only Administrators can access the plugin and create and edit redirections.

= Can I bulk disable/delete redirections created by this plugin?  =

Yes, you can easily disable single or bulk redirection rules or specific redirections.

= Are redirections disabled if I uninstall the plugin?  =

All redirection rules and specific redirects will be disabled when the Redirection plugin is disabled/uninstalled. But, you can disable redirects without disabling the plugin.

= Can I redirect 404 to homepage? =

Yes! You can also create redirection 404s to any other page if you like. And there is a brand new feature of redirection 404 to a Random Similar Post.

= How to find/track dead links on my site? =

With the brand new feature “Redirection and 404 Logs” you can easily spot if there are any invalid links that your visitors are trying to reach, and best of all, you can fix them with one click on the button. Also, if you set up a Redirection Rule “Redirect all URLs which: are 404s to Random Similar Post” - you will basically fix all 404s on your site with one rule.

= How are you better than other redirection plugins? =

Redirection plugin is designed to be newbie-friendly, with a professionally designed interface, and simplified functions available to help users that are not familiar with, e.g. RegEx. Upcoming features will also be handy for not-too-technical users and users who changed their links (and permalinks).

= Can I redirect all pages on my site to a homepage (or another specific page)? =

Yes, you can. Re how to do it, you simply navigate to Redirection Rules (second tab from the left), then add new redir, under the first dropdown select “All URLs” and on the right define a destination. Note that adding this redirection will *not* cause red alert of infinite loop error, and /wp-admin section of your site is also exempted from this rule.

= Is the plugin also available in my language? =

So far we have translated the plugin into these languages:

Arabic: [قم بتعيين 301 ، 307 ، 404 ، إعادة توجيه أخرى.](https://ar.wordpress.org/plugins/redirect-redirection/)
Chinese (China): [设置 301、307、404、其他重定向。](https://cn.wordpress.org/plugins/redirect-redirection/)
Croatian: [Postavite 301, 307, 404 i druga preusmjeravanja.](https://hr.wordpress.org/plugins/redirect-redirection/)
Dutch: [Stel 301, 307, 404 in, een andere omleiding.](https://nl.wordpress.org/plugins/redirect-redirection/)
English: [Setup 301, 307, 404 and other redirections.](https://wordpress.org/plugins/redirect-redirection/)
Finnish: [Aseta 301, 307, 404 ja muut uudelleenohjaukset.](https://fi.wordpress.org/plugins/redirect-redirection/)
French (France): [Définissez 301, 307, 404, une autre redirection.](https://fr.wordpress.org/plugins/redirect-redirection/)
German: [Stellen Sie 301, 307, 404 und andere Umleitungen ein.](https://de.wordpress.org/plugins/redirect-redirection/)
Greek: [Ορίστε 301, 307, 404, άλλες ανακατευθύνσεις.](https://el.wordpress.org/plugins/redirect-redirection/)
Hungarian: [Állítsa be a 301, 307, 404 és egyéb átirányításokat.](https://hu.wordpress.org/plugins/redirect-redirection/)
Indonesian: [Setel 301, 307, 404, pengalihan lainnya.](https://id.wordpress.org/plugins/redirect-redirection/)
Italian: [Impostare 301, 307, 404, un altro reindirizzamento.](https://it.wordpress.org/plugins/redirect-redirection/)
Persian: [301، 307، 404، یک تغییر مسیر دیگر را تنظیم کنید.](https://fa.wordpress.org/plugins/redirect-redirection/)
Polish: [Ustaw 301, 307, 404, inne przekierowania.](https://pl.wordpress.org/plugins/redirect-redirection/)
Portuguese (Brazil): [Defina 301, 307, 404, outros redirecionamentos.](https://br.wordpress.org/plugins/redirect-redirection/)
Russian: [Установите 301, 307, 404, другие редиректы.](https://ru.wordpress.org/plugins/redirect-redirection/)
Spanish: [Configure 301, 307, 404 y otras redirecciones.](https://es.wordpress.org/plugins/redirect-redirection/)
Turkish: [301'e, 307'e, 404'e ve diğerlerine yönelik yönlendirme kurun.](https://tr.wordpress.org/plugins/redirect-redirection/)
Vietnamese: [Đặt 301, 307, 404, một chuyển hướng khác.](https://vi.wordpress.org/plugins/redirect-redirection/)

== Screenshots ==

1. Specific URL Redirections
2. Redirection Rules
3. Redirection rules explained
4. Advanced options
5. HTTP codes explanation

== Changelog ==

= 1.2.7 =
* Enqueued tooltip CSS and JS files in IRRPSettings class for enhanced user experience.
* Removed unnecessary comments regarding tooltips in single-redirection.php layout.
* Ensured tooltip functionality is ready for input fields in redirection settings.
* Increase the redirections per page to 30 instead of 10
* Tested with WordPress 6.8.1

= 1.2.6 =
* Increased URL field length from 255 to 1000 characters for more flexibility in “from” and “to” fields.
* Ensure redirects settings are saved correctly.
* Enhanced URL Parameters Matching.

= 1.2.5 =
* Tested with WordPress 6.7.1
* Fixed parameters being cut off after an encoded slash.
* Fixed loading translations too early.

= 1.2.4 =
* Tested with WordPress 6.6.2-RC1
* Add "ask us" for regex on rules explanations
* Fixed delete logs option where the updated value wasn't visible automatically
* Slight UI improvements

= 1.2.3 =
* Tested with WordPress 6.6
* Minor performance improvements 
* Improvements for PHP 8 utilization
* Adjusted domain validation 
* Added pagination with "Show more" in logs

= 1.2.2 =
* [IMPROVED] Added support to non-latin characters and special characters in URL input
* [FIX] Reverted sanitization process from v1.2.0
* [NOTE] Tested up with WordPress v6.5 (beta)

= 1.2.1 =
* [IMPROVED] Redirections based on user language should be more accurate and even more sophisticated
* [IMPROVED] URL verification - now it's not required to use http/https
* [NEW] Absolute and relative path is now supported in simple rediretion text areas (/from -> /to)
* [NEW] Added support for websites without 'www.' and destination URL with ".pdf, .png, etc."
* [NEW] Inclusion & Exclusion rules are now available for all-urls rule
* [FIX] Fixed issue when rule 404-s redirections got removed after page reload
* [FIX] Fixed corner case issues with URL sanitization when non alphanumeric URLs were used
* [REMOVED/DEPRECATED] Automatic redirect for post and pages, it has no effect for existing users
* [NOTE] Tested up with WordPress v6.4.3

= 1.2.0 =
* [NOTE] Upgraded analyst module to latest version
* [NOTE] Tested up to PHP v8.3

= 1.1.9 =
* [NEW] Now user can switch off or switch on 404 error & redirect logs permanently
* [FIX] Redirects that ends with "f" will now work properly
* [FIX] Destination values will be pre-propagated now during redirection edition
* [FIX] False positive errors during bulk enable of redirections

= 1.1.8 =
* [NEW] Added feature "log me where I finished", redirects you after log in where you logged out
* [NEW] Now it is possible to select multiple user roles to match redirection
* [NEW] You can now set regex rule for referrer redirection advanced option
* [NEW] Added redirection option for URL "ends-with"
* [FIX] All redirects will be now trimmed by %2F encoded character (without trailing slash)
* [FIX] Now wp-admin and wp-login is additionally protected from redirection
* [FIX] Review module updated to latest version which prevents it to display in edit screens
* [FIX] Adjusted rules logic, to make sure all will be checked properly for 404 redirections
* [CHANGE] Modified default settings for our new users, ignore trailing slashes, ignore lower/upper case, ignore parameters
* [CHANGE] Improved 404 logs, added "count" to prevent duplicates and allow to add new single click redirection
* [CHANGE] Logs will now be removed after 7 days without update, previously 1 day

= 1.1.7 =
* Improved stability on larger sites
* Tested with WordPress 6.4
* Updated opt-in module
* Improved RegExp redirections 
* Adjusted few typos in notifications
* Added more detailed notifications 
* Prevented possibility of two 'is-404' redirections
* Prevented possibility of two 'all-urls' redirections
* Updated Carousel module

= 1.1.6 =
* Added custom permission for plugin access: redirect_redirection_admin
* Changed way how advanced options section works
* Improved user experience in terms of adding new rules and editing them
* Improved stability of redirections (to be sensitive enough in corner cases)
* Updated banner module to exclude conflicts with other plugins
* Resolved warnings and notices in PHP 8.2
* Tested with WordPress 6.3.1
* Added review module

= 1.1.5 =
* Improved UTF-8 URLs matching for additonal languages.
* Fixed redirection with parameters (simple & advanced)
* Added nonce verification for plugin deinstallation

= 1.1.4 =
* Limited access to the plugin to site administrators only
* Added nonce verification
* Added possibility to add redirection headers
* Fixed deactivation feedback
* Adjusted minor arrow display issues
* Tested with WP 6.2-Beta2
* Fixed UTF-8 URLs redirections
* Removed BF module
* Updated carrousel & opt-in module

= 1.1.3 =
* Adjusted PHP compatibility

= 1.1.2 =
* Added black-friday theme (only for that period)
* Tested up to WordPress 6.1.1

= 1.1.1 =
* Reverted change of template redirection priority

= 1.1.0 =
* Tested up to WP v6.1-RC5
* Added optional opt-in module
* Updated try it option in readme
* Fixed issue with logs clean-up - database errors PHP 8+
* Removed unnecessary files from codebase
* Adjusted redirection, it won't redirect twice before destination site

= 1.0.9 =
* Fixed issues with version 1.0.8 where template redirection support was required

= 1.0.8 =
* Fixed local redirect loops (e.g. homepage -> post)
* Adjusted our URL validator
* Changed hook of redirect execution
* Removed unwanted error_logging
* Tested with WordPress 6.0.1

= 1.0.7 =
* Tested with WordPress Beta 6.0
* Improved database management
* Added new notifications and notices
* Allowed redirections inside wp-admin
* Added all-urls rule for redirection
* Added support for PHP 5.6 (older sites)
* Added carrousel

= 1.0.6 =
* Added Redirection & 404 Logs feature; fixed an issue with redirection to another website and the issue with the set cookie
* Tested with PHP 8.0 and 8.1
* Tested with WordPress 5.9

= 1.0.5 =
* Added redirection by browser language
* Fixed bugs with language detection

= 1.0.4 =
* Added redirection rule 404s to Random Similar Post

= 1.0.3 =
* Added redirection rule 404s to Specific URL

= 1.0.2 =
* Make plugin translatable ready
* Tested up to WordPress 5.8

= 1.0.1 =
* Fixed mb functions on servers without mbstring extenstion
* Added auto redirection on activation
* Added screenshots

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.2.7 =
* Enqueued tooltip CSS and JS files in IRRPSettings class for enhanced user experience.
* Removed unnecessary comments regarding tooltips in single-redirection.php layout.
* Ensured tooltip functionality is ready for input fields in redirection settings.
* Increase the redirections per page to 30 instead of 10
* Tested with WordPress 6.8.1