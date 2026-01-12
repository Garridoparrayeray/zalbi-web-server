=== Duplicate Post ===
Contributors: CopyDeletePosts, copydelete
Tags: Duplicate post, Copy posts, Copy pages, Duplicate posts, Duplicate pages
Requires at least: 4.6
Tested up to: 6.8
Stable tag: 1.5.0
License: GPLv3
Requires PHP: 5.6

Duplicate post

== Description ==

**Try it out on your free dummy site: Click here => [https://tastewp.com/plugins/copy-delete-posts](https://demo.tastewp.com/cdp).**
(this trick works for all plugins in the WP repo - just replace "wordpress" with "tastewp" in the URL)

Massively increase your WordPress productivity!

Copy Page plugin makes it super-easy to duplicate pages or copy posts - and delete them again!

And why is it handy to duplicate pages? Here are just some of the use cases:

- Duplicate pages to make short work of using again the same elements you repeatedly use (e.g. text paragraphs, images, video, featured image, etc.)
- Create a variation of a page or post fast to modify it and compare side by side (for yourself, your client or company)
- Create one perfect set of page templates and then re-use them for different projects, clients or products
- Apply a facelift to a specific page but keep the older version in case you want to switch back to it anytime
- Make a "holiday special" page template and use it for different holidays with respective adjustments
- Create duplicates for pages used in page builders with their custom settings

You can as well run a load-test on your server by duplicating as much as 1,000 pages, and track how the server behaves. Copy Page plugin also makes it super-easy for you to bulk-delete pages and posts whenever you feel it’s time for a clean-up!

**How to use it**

Two-minute video tutorial and you are ready to use it, that’s how simple Copy Page is!

[youtube https://youtu.be/1KXLuKhNCR4]

After installation you'll see a new copy page button which, on mouse-over, displays a tooltip (see screenshot) which allows you to copy pages or copy posts with various options:

- Copy page or duplicate post instantly with a single click
- Expand to see additional copy page options and specify which elements should be copied for the current copy page or copy post action

The new copy page button will be available on:

- All Pages and All Posts screens
- Edit screens (duplicate page or duplicate post on the respective edit page/post screens)
- Admin bar (at the top)
- Bulk-option to copy pages or copy posts on All Pages and All Posts screens
- Gutenberg editor


You can hide copy page or copy post button on any of these places from the Copy Page plugin menu (Section: Other options). Copy page function on the editor screens allows fast and easy multiplying of pages you are working on at the moment (and open it in the new browser tab immediately, too), so you can quickly make a couple of variations, pick whichever you like better, and afterward use the Delete duplicate posts/pages tool of Copy Page plugin to remove duplicate(s) that you dislike.

Copy Page plugin also provides an intuitive naming tool to define how the cloned pages or posts will be named (e.g. you can add the time and date of copying, or incremental counter, etc.). This way you can multiply page that will have a short name, e.g. “ExamplePage - #4” - where the number at the end will increase with each copy made; or you can duplicate page with much more detailed name of the copy, such as: “ExamplePage copied at 14:22:58 on Sunday, December 1st, 2021” - thankfully to PHP date/time shortcodes being supported in the custom date settings. Long names often make it easy to differentiate the clone post, either to remove duplicate or to e.g. edit it.

Want to duplicate page but also it’s child-page(s)? You can multiply pages altogether with child-pages with all the copy settings currently applied to the parent page copy.

Other options available when you copy pages:

- Specify where you will be navigated to after you copy page or copy post
- Specify which users (WP roles) will be granted to copy pages or copy posts
- Specify which content types will be allowed to be copied - copy pages, copy posts, and copy custom post types
- Enable/disable the display of a reference to the original of copied page or post

Not only can you clone pages or clone posts easily, but the Copy Page plugin also provides a highly developed tool to Delete duplicate posts/pages.

As part of this cleanup process, you can specify:

- Where the plugin will look for duplicates (i.e. delete pages, delete posts or delete custom posts)
- What will be considered as a duplicate page or duplicate post (i.e. will it be the same title, slug, excerpt or word count)
- Which version of duplicates you want to keep: oldest version (original) or newest version (the latest duplicate)

Even if you used this replicator tool to multiply pages or multiply posts in huge numbers, and you want to use this tool to trash duplicates every now and then, but leave out a few specific ones - you will be able to easily find duplicates when you use our duplicates scanner. After setting the parameters to find duplicates, you can use a search box to make sure you don’t delete duplicates you don’t want to, or remove duplicates to the last one (original included!). The duplicate checker tool can help you find duplicate and immediately visually check the clone page by clicking on the URL/slug link, in case you can’t tell by the name or the date clone page was created.

You can select to throttle the delete pages or delete posts process - which you may want to do when you’re on a slow server (note, however: the plugin codes to delete posts or delete pages are already optimized, so usually there shouldn’t be an issue).

Copy Page is a cloner tool with a beautiful, modern design and features going beyond today’s post duplicator tools. We hope that Copy Page will become your favorite posts duplicator tool :)

The free Copy Page plugin offers a lot of options - if you want even more options to copy pages or copy posts, then check out the [premium Copy Page plugin](https://sellcodes.com/CylMIdJD) which offers the following additional features:

- Use several configuration sets to copy pages or delete posts - useful when you want to quickly switch between the copy types, without having to go to the settings page.
- Export and import configuration sets - a handy tool for all of us who manage multiple sites and regularly replicate posts.
- Also include information from third party plugins when you copy pages (e.g. Yoast-information linked to pages/posts)
- Replicate pages across multisite will also prove to be a real time-saver for multisite administrators
- Automatically delete pages or posts - extremely useful for users that multiply posts or pages at high levels.
- Apply automatic redirects for deleted pages or posts

Just try it out! You'll love it :)

This plugin is part of the Inisev product family - [check out our other products](https://inisev.com).


== Installation ==

= Admin Installer via search =
1. Visit the Add New plugin screen and search for "Copy & Delete Posts".
2. Click the "Install Now" button.
3. Activate the plugin.
4. The plugin should be shown below settings menu.

= Admin Installer via zip =
1. Visit the Add New plugin screen and click the "Upload Plugin" button.
2. Click the "Browse..." button and select the zip file of our plugin.
3. Click "Install Now" button.
4. Once uploading is done, activate Copy & Delete Posts.
5. The plugin should be shown below the settings menu.

== Frequently Asked Questions ==

= It seems the post deletion process doesn’t work. Why? =
If you are trying to delete posts and it doesn’t work try to append your wp-config.php with this line of code:
`define('ALTERNATE_WP_CRON', true);`
Does it work if you try to delete posts then? If not, please reach out to us in the support forum.

= When I copy post or copy page, why is title of the duplicate not the same?  =
If you copy posts or copy pages and want the new versions to have exactly the same title as the original post, make sure that prefix and suffix fields are blank in the “What name(s) should the copies have?” section of the Copy Delete Posts plugin area in the WordPress Dashboard.

= If I duplicate posts, how do I know what their original page was?  =
It can be a challenge to keep track of the original content if you duplicate posts. To prevent this we suggest to not leave the prefix and suffix fields empty (which define the name of the new posts) if you duplicate posts. However, even if you want to duplicate posts without any prefix or suffix, you can solve the issue as follows: Go to section “Other options” (on the duplicate post plugin configuration page), and at the bottom of this section you will find the option “Show reference to original item?”. Check this to ensure you can always keep track of original posts when duplicate pages or posts.

= Can I limit who can duplicate posts on my site? =
By default only Administrators can access the plugin and copy posts or copy pages. You can extend these permissions to other user roles by going to the section “Other options”, and then tick boxes next to WP user roles that you want to give permission to. Then also those roles can duplicate posts (or delete posts).

= I want to duplicate posts *only*, i.e. not duplicate pages. Is that possible? =
You can limit the features to duplicate posts only by going to the “Other options” - section and select where it says “Content types which can be copied” to only copy posts, copy pages, copy custom posts or all of these.

= How can I make bulk copies? =
If you want to duplicate posts en masse, select the copy posts option in the “Bulk actions” menu. You’ll see the lightbox asking you to specify your duplicate post options (i.e. which elements to copy).

= I can duplicate posts but it takes a long time. Why? =
If you duplicate posts and it takes long, then you may have selected to include attachments in the duplicate posts configurations. Go to the second section titled “Which elements should be copied?” and de-select the attachments option to exclude those when you duplicate posts.

= Can I bulk delete posts created by this plugin?  =
To easily clean posts or delete duplicate pages that were created by this plugin, go to ”Delete duplicate posts or pages” section, tab “Manual cleanup”. Select Posts, Pages and Custom Posts, and uncheck all other filters, then hit the Scan button. In the empty results list, you will see the message “Click here to show all copies…” - “here” link will show you all posts and pages created by our multiplier plugin.

= Which dupicate post features do you have which the other plugins don’t?  =
Other duplicate post plugins mostly only allow you to duplicate post to the same site. With the Copy Delete Posts plugin (premium version) you can duplicate post to other sites, e.g. duplicate post to a multisite, or duplicate post to a site on a different domain altogether (we’re currently working on this duplicate post functionality). Also, other duplicate post plugins don’t give you the granularity to define how to duplicate post, e.g. which elements specifically should appear on the cloned posts.

= Is this plugin GDPR friendly? =
Copy Delete Posts WordPress plugin doesn’t store any site visitor information so it is completely GDPR friendly.

= ACF compatibility =
ACF is fully supported by Copy Delete Post Premium, as ACF is something more than a simple post. The plugin can only cop native posts and pages ( that are aligned with WordPress standards ). ACF does not stick with these standards as they put multiple posts attached to one post ID, which is visible on the list, while others are hidden.

= Is the plugin also available in my language? =
So far we have translated the plugin into these languages:

Arabic: [انسخ المنشورات وانسخ الصفحات ونسخ المنشورات المخصصة وحذف التكرارات.](https://ar.wordpress.org/plugins/copy-delete-posts/)
Chinese (China): [复制帖子、复制页面、复制自定义帖子和删除重复项。](https://cn.wordpress.org/plugins/copy-delete-posts/)
Croatian: [Kopirajte postove, kopirajte stranice, duplicirajte prilagođene postove i izbrišite duplikate.](https://hr.wordpress.org/plugins/copy-delete-posts/)
Dutch: [Kopieer berichten, kopieer pagina's, dupliceer aangepaste berichten en verwijder duplicaten.](https://nl.wordpress.org/plugins/copy-delete-posts/)
English: [Copy pages, copy posts, and delete the duplicate post again in one go](https://wordpress.org/plugins/copy-delete-posts/)
Finnish: [Kopioi viestejä, kopioi sivuja, monista mukautettuja viestejä ja poista kaksoiskappaleita.](https://fi.wordpress.org/plugins/copy-delete-posts/)
French (France): [Copiez les publications, copiez les pages, dupliquez les publications personnalisées et supprimez les doublons.](https://fr.wordpress.org/plugins/copy-delete-posts/)
German: [Kopieren Sie Beiträge, kopieren Sie Seiten, duplizieren Sie benutzerdefinierte Beiträge und löschen Sie Duplikate.](https://de.wordpress.org/plugins/copy-delete-posts/)
Greek: [Αντιγράψτε αναρτήσεις, αντιγράψτε σελίδες, αντιγράψτε προσαρμοσμένες αναρτήσεις και διαγράψτε διπλότυπα.](https://el.wordpress.org/plugins/copy-delete-posts/)
Hungarian: [Bejegyzések másolása, oldalak másolása, egyéni bejegyzések másolása és ismétlődések törlése.](https://hu.wordpress.org/plugins/copy-delete-posts/)
Indonesian: [Salin posting, salin halaman, duplikat posting kustom, dan hapus duplikat.](https://id.wordpress.org/plugins/copy-delete-posts/)
Italian: [Copia post, copia pagine, duplica post personalizzati ed elimina duplicati.](https://it.wordpress.org/plugins/copy-delete-posts/)
Persian: [پست ها را کپی کنید، صفحات را کپی کنید، پست های سفارشی را تکرار کنید، و موارد تکراری را حذف کنید.](https://fa.wordpress.org/plugins/copy-delete-posts/)
Polish: [Kopiuj posty, kopiuj strony, duplikuj posty niestandardowe i usuwaj duplikaty.](https://pl.wordpress.org/plugins/copy-delete-posts/)
Portuguese (Brazil): [Copie postagens, copie páginas, duplique postagens personalizadas e exclua duplicatas.](https://br.wordpress.org/plugins/copy-delete-posts/)
Russian: [Копируйте сообщения, копируйте страницы, дублируйте пользовательские сообщения и удаляйте дубликаты.](https://ru.wordpress.org/plugins/copy-delete-posts/)
Spanish: [Copie publicaciones, copie páginas, duplique publicaciones personalizadas y elimine duplicados.](https://es.wordpress.org/plugins/copy-delete-posts/)
Turkish: [Gönderi kopyalayın, sayfa kopyalayın, özel tasarlanmış gönderileri çoğaltın, ve kopyaları silin.](https://tr.wordpress.org/plugins/copy-delete-posts/)
Vietnamese: [Sao chép bài đăng, sao chép trang, sao chép bài đăng tùy chỉnh và xóa bản sao.](https://vi.wordpress.org/plugins/copy-delete-posts/)

== Screenshots ==
1. Plugin settings page
2. Copy preset settings
3. Customizable naming system
4. Global settings & permission system
5. Manual clean up
6. Quick-copy tooltip
7. Tooltip individual copy
8. Copy from Gutenberg editor

== Changelog ==

= 1.5.0 =
* Fixed issues with mailpoet
* Tested with WP 6.8

= 1.4.9 =
* Removed optimization logs for users to prevent performance optimizations of the plugin in future
* [PRO] Improved cross-site duplication of posts

= 1.4.8 =
* Replace the super admin check with a verification of CDP permission for the current user.
* Implement a check to ensure the current user has appropriate access rights to the target site before allowing cross-site post copying. This prevents unauthorized users from copying posts to sites they don't have access to.

= 1.4.7 =
* Tested with WordPress 6.6
* Minor performance improvements 
* Improvements for PHP 8 utilization
* Fixed taxonomy field when editing with quick edit

= 1.4.6 =
* [NOTE] Changed plugin's author to our company name
* [NOTE] Tested up with WordPress v6.4.3 
* [NOTE] Thank you so much for 300,000+ active installs 💚 (12.02.2024)

= 1.4.5 =
* [PRO/CHANGE] Default type conversion will be now post not page (custom type -> post type by default)
* [PRO/FIX] Adjusted category duplication/assign method (useful for cross site duplication)
* [FIX] Resolved issues with custom type category/tags duplication
* [FIX] Review banner won't show up on post edit pages now
* [NEW] Added smart taxonomy handler which can assign custom taxonomy to core fields
* [NOTE] Upgraded analyst module to latest version
* [NOTE] Tested with WordPress v6.4.2
* [NOTE] Tested up to PHP v8.3

= 1.4.4 =
* [Pro] Improved domain replacement 
* [Pro] Fixed profiles and other modals center position
* [Pro] Increased stability of the plugin and improved load time
* [Pro] Fixed cross-subsite taxonomy duplication (categories, tags, etc.)
* [Pro] Now our plugin won't create new taxonomy if there is existing one by name
* [Pro] Improved ACF duplication, tested with latest version
* [Pro] Resolved known bugs with ACF plugin and added support for domain auto-replacement
* [Pro] Improved featured image and attachment duplication between subsites
* Tested up with WordPress 6.4.1
* Added improvements for PHP 8.2
* Fixed gutenberg copy button, now it will be displayed properly.
* Updated "Try it out" module which gives each user individual decition about this module
* Fixed issues with selecting profile, custom rules during bulk duplication
* Fixed issues within copy modal with incorrect size of fields
* Adjusted look of sections to make it more "popping off"
* Front end duplication visually improved

= 1.4.3 =
* Forced "Try it out" module to be disabled by default, user can still enable it by manually.

= 1.4.2 =
* Added additional nonce verification

= 1.4.1 =
* Tested with WP 6.3 RC
* Added possibility for automatic Elementor Cache clearing after duplication
* Fixed localization issues, when plugin hanged on previously selected language
* Updated all shared modules to their latest versions

= 1.4.0 =
* Fixed tooltip attribute in Manual Cleanup section
* Tested with PHP 8.2 and WordPress 6.2.2
* Added support for WooCommerce Price per User options
* Prepared for further expand of supported extensions
* Fixed incorrect ID naming in sidebar menu
* Upgraded carrousel banner module version

= 1.3.9 =
* Tested up with WordPress 6.2
* Removed two unused modules
* Updated carrousel module
* Fixed old database version compatibility
* Extended TIF module for everyone

= 1.3.8 =
* Tested up WordPress 6.2-Beta1
* [Premium] Added full ACF duplication support
* Adjusted proper duplication of WooCommerce products
* Fixed duplication of WooCommerce variations
* Resolved issues with child-post custom types
* Extended new module
* Minimized styling conflicts in custom post type pages

= 1.3.7 =
* Included new module
* Fixed issues with deactivation feedback
* Corrected display of arrow

= 1.3.6 =
* Adjusted PHP compatibility

= 1.3.5 =
* Added black-friday theme (only for that period)
* Tested up to WordPress 6.1.1

= 1.3.4 =
* Improved premium plugin performance
* Tested with final WordPress 6.1 version

= 1.3.3 =
* Tested with WordPress 6.1-RC5 + Multisite
* Fully tested on PHP 7.4, 8.0, 8.1
* Removed function that could cause conflicts

= 1.3.2 =
* NEW: Added automatic creation of non-existing categories for subsite duplication
* Fixed issues with assigning taxonomy on new posts
* Resolved issues with featured image duplication between sites
* Tested with WordPress 6.0.2 + Multisite (PHP 8)

= 1.3.1 =
* Version fully tested with WordPress 6.0.1 + Multisite
* Removed unnecessary debug code
* Fixed issues with falsive attachment duplication
* Fixed issue inside tooltip while redirect to edit screen option was enabled
* Fixed issue while during duplication notice "Please select settings" appeared
* Fixed issue with multi-post duplication to different subsite
* Fixed post type restrictions, copy option won't appear in dropdown while copying is forbidden
* Fixed corner cases where copy action button was not displayed in subsites
* Fixed issues with backslashes and unicode characters - they should copied correctly now
* Default WordPress Posts and Pages are not longer treated as custom posts
[AND MORE]

== Upgrade Notice ==
= 1.5.0 =
What's new in 1.5.0?
* Fixed issues with mailpoet
* Tested with WP 6.8