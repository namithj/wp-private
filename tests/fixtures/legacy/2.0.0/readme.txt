=== WP Private – Gated Content for Posts & Pages ===
Contributors: namithjawahar
Tags: content restriction, members only, login, shortcode, private content
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide part of a post or page from logged-out visitors and show a login form or login link in its place. One shortcode, no setup.

== Description ==

Sometimes most of a post is for everyone and one part is for people with an account: the meeting minutes, the download link, the second half of the article.

Wrap that part in `[protected]` and `[/protected]`. Logged-out visitors see a login form, or a login link, exactly where the hidden part would be. After logging in they are returned to the same post and see everything.

**What it does**

* Hides any part of a post or page from logged-out visitors. Use it as many times as you like on one page.
* Shows a login form, a login and register link, or nothing in place of the hidden part.
* Lets you write the text shown around the login prompt.
* Lets you exclude individual users even when they are logged in.
* Works on activation. Settings are optional.

**What it does not do**

WP Private gates part of a post. It is not a membership suite with levels and billing, and it does not protect files.

**What is protected**

* The post or page itself: Yes
* Blog, archive and home page listings: Yes
* Automatic excerpts: Yes
* RSS and Atom feeds: Yes
* REST API rendered content: Yes
* Site search: **No.** A search for a word that appears only in the hidden part still returns the post, without showing the hidden text. A fix is planned for a later release.
* Other plugins that read raw post content, such as some SEO, search or AI tools: **Not controlled by this plugin.** Check each one.
* Users who can edit the post: They always see the full content in the editor.
* If the plugin is deactivated or deleted: The shortcode stops working and the hidden text becomes visible together with the shortcode tags.

**Privacy**

WP Private stores its settings and a list of excluded user IDs in your site's database. It sets no cookies of its own, loads no scripts or styles on your pages, and contacts no external server. If you delete the plugin, these settings stay in the database (see the FAQ for the list).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the Plugins screen, and activate it.
2. Edit a post or page and put `[protected]` before the part to hide and `[/protected]` after it, in a Shortcode block or in paragraph text.
3. View the post logged out. The login form appears where the hidden part was. Settings are optional: open Settings > WP Private to change the prompt.

== Frequently Asked Questions ==

= How do I hide part of a post? =

Put the part between the two tags:

`
Everyone can read this.
[protected]Only logged-in users can read this.[/protected]
Everyone can read this too.
`

= Can I use it more than once on a page? =

Yes. Every `[protected]` ... `[/protected]` pair is handled on its own.

= What do logged-out visitors see? Can I change the text? =

By default, a short line asking them to log in, followed by a login form. In Settings > WP Private you can choose a login form, login and register links (the register link appears only when your site allows registration), or nothing at all, and you can write the HTML shown before and after the prompt.

= Where does the visitor go after logging in? =

Back to the same post, also on sites installed in a subdirectory.

= Can I show a login link instead of a form? Can I use my own login page? =

Yes to both, in Settings > WP Private. The shortcode `[loginform]` shows a login form anywhere in a post or page. Logged-in users see a greeting and a logout link instead.

= Can I stop a specific logged-in user from seeing protected parts? =

Yes. Add the user to the block list in Settings > WP Private. Blocked users see the message you write there instead of the content.

= Does it work with the block editor? =

Yes: put the shortcode in a Shortcode block or in paragraph text. A dedicated block is planned.

= Is the hidden text really hidden? =

From logged-out visitors, in the places listed in "What is protected" above. Read the search row of that table: a search can still return the post, though never the hidden text. Plugins that read raw post content are not controlled by WP Private.

= Does it work with caching plugins? =

Page caches normally serve cached pages to logged-out visitors only, which is the case the gate is built for. Tested configurations will be listed in a later release.

= I updated from 1.x and the login form moved. Why? =

From 2.0.0 the login form appears inside the post, where the hidden part is, instead of at the top of the page. Your `[protected]` shortcodes are unchanged and your settings were moved to a new format automatically when the plugin first ran.

= I used `<!--protected-->` comments in version 1.4 or 1.5. Do they still hide content? =

No. Comment-style markers stopped working in 1.6 and do not work in 2.x. Content that you hid that way is visible. Replace the markers with `[protected]` and `[/protected]`.

= Can I go back to 1.6.1? =

Going back to 1.6.1 is not supported. If you do it, `[protected]` still hides content, but 1.6.1 reads the settings as they were at the moment you upgraded, so changes made in 2.x, including the block list, do not apply. On a site first installed on 2.x, 1.6.1 hides protected parts from everyone until its settings are saved. Going forward to 2.x again keeps your 2.x settings and ignores edits made in 1.6.1.

= What happens to my content if I remove the plugin? =

The shortcode stops working and the hidden text becomes visible, together with the shortcode tags. Remove the tags and review the content before you delete the plugin.

= What does the plugin leave behind if I delete it? =

Its options stay in the database, so reinstalling restores your settings. Nothing is deleted on upgrade or on deletion. The options are:

* `wp_private_version`
* `wp_private_settings_type`
* `wp_private_settings_before_html`
* `wp_private_settings_after_html`
* `wp_private_settings_authorized_text`
* `wp_private_settings_blocked_users`
* `wp_private_settings_custom_login_page_url`

Options written by versions 1.x are also left as they were, and 2.x no longer reads them after the one-time conversion: `wp_private_settings`, `wp_private_replacement_type`, `wp_private_before_html`, `wp_private_after_html`, `wp_private_not_authorized_text`, `wp_private_selected_users`, `wp_private_custom_login_page_url` and `wp_private_linkback_enable`. To remove them, delete them by hand (for example with WP-CLI: `wp option delete wp_private_version`).

= Why was some of my HTML removed when I saved the settings? =

Settings that accept HTML are filtered with the same rules as post content for users who do not have the `unfiltered_html` capability (for example administrators on multisite sub-sites). Administrators with that capability can save any markup. HTML you saved with an earlier version is output as stored until you change it.

= The search finds a post with hidden text. Is that a bug? =

It is a known limit. WordPress searches the stored text of a post, including the hidden part. A search for a word that appears only in the hidden part returns the post, and shows its title and the visible text, never the hidden part. A fix is planned for a later release.

= How do I report a security problem? =

Please do not post it in the support forum. Email security@smartlogix.co.in. A policy is in SECURITY.md in the plugin's repository.

== Screenshots ==

1. A post on a default block theme, logged out: the teaser, then the login form in place of the hidden part.
2. The same post, logged in: the full text.
3. The block editor: `[protected]` and `[/protected]` in Shortcode blocks around the paragraphs to hide.
4. The settings page (Settings → WP Private).
5. The login and register links variant, logged out.
6. The block list (Settings → WP Private, Blocked users tab): blocked users stay ticked at the top; search to add more.

== Changelog ==

= 2.0.0 =
A maintenance release after a long gap. The login form now appears inside the post where the hidden part is. Protected content works immediately after activation. Login links return you to the post. Settings are sanitised. Requires WordPress 6.8 and PHP 7.4. Your `[protected]` shortcodes are unchanged and your settings are moved to a new format automatically; going back to 1.6.1 afterwards is not supported.

== Upgrade Notice ==

= 2.0.0 =
A maintenance release after a long gap. Your [protected] shortcodes are unchanged and settings convert automatically; going back to 1.6.1 is not supported. The login form now sits inside the post. Needs WordPress 6.8 and PHP 7.4; older sites stay on 1.6.1.
