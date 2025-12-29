=== Worry Proof Backup ===
Contributors: miketropi
Donate link: https://github.com/miketropi/worry-proof-backup
Tags: backup, restore, import, export, database
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Effortless, reliable WordPress backups and restores—database, plugins, themes, uploads, and more.

== Description ==
WP Backup is a comprehensive backup solution for WordPress. Effortlessly create and restore backups of your entire site, including database, plugins, themes, uploads, and demo content. Designed for reliability, speed, and ease of use, with a modern React-powered admin interface.

= Features =
* One-Click Backup & Restore
* Database Import/Export
* Plugin & Theme Backup
* Uploads & Folder Backup
* Demo Content Import (for theme/plugin developers - coming soon)
* Modern Admin UI (React-powered)
* AJAX & Nonce Security

= Demo Content Import Solution =
WP Backup provides a developer-friendly way to package and import demo content, making it ideal for theme and plugin authors who want to offer a quick start for their users.

= ⚠️ PHP 8.0 or Higher Required – Why? =

Worry Proof Backup is built with modern techniques to ensure your data restores safely, quickly, and reliably—even when working with large backup files.

We require PHP 8.0 or higher for the following reasons:

== 🚀 1. Superior Performance ==
PHP 8 introduces Just-In-Time (JIT) compilation, which significantly speeds up operations like:
* Extracting ZIP archives with tens of thousands of files
* Reading and writing large file contents in memory
* Fast, stable looping over file lists

== 🔒 2. Improved Stability and Accuracy ==
In PHP 7, some built-in functions like `stream_get_contents()` can behave inconsistently with large files or streams. PHP 8 provides:
* More reliable file extraction
* Fewer write failures and timeouts
* Better handling of memory streams

== 🧠 3. Better File System Handling ==
Our plugin supports restoring entire folders with thousands of files, which demands fast and reliable filesystem operations—something PHP 8 handles far better than PHP 7.

---

= 💡 Still Using PHP 7? =
We strongly recommend upgrading to PHP 8.0+ for optimal compatibility, performance, and security—not just for this plugin, but for your entire WordPress site.

Most hosting providers support PHP 8—feel free to reach out to your hosting support team and ask for an upgrade.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/worry-proof-backup` directory, or install via the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **WP Backup** menu in your WordPress admin tools sidebar.

== Frequently Asked Questions ==
= What does WP Backup backup? =
It can backup your database, plugins, themes, uploads, and solution for demo content import one Click.

= Is it secure? =
Yes, all operations are secured with WordPress nonces and AJAX.

= Can I use this to migrate my site? =
Yes, you can use the import/export tools to move your site or demo content between environments.

== Screenshots ==
1. Admin dashboard with backup options
2. Backup list and restore options
3. Import/export tools
4. Admin dashboard interface

== External services ==
* No External services

== Github repository ==
* [Plugin Source](https://github.com/miketropi/worry-proof-backup)

= 0.1.5 =
* Added new "Backup File System V2" class with support for customizable chunk size during file backups.
* Improved handling of large sites and backup stability.
* Preparation for advanced backup and restore features.

== Changelog ==
= 0.1.4 =
* Enhanced security (new class WORRPB_Type_Validator)

== Changelog ==
= 0.1.2 =
* Added automated backup scheduling (weekly, monthly)
* Added email notifications for completed backups
* Enhanced security with improved nonce validation
* Updated plugin branding to "Worry Proof Backup"
* Improved backup folder organization
* Added backup completion status tracking
* Enhanced admin interface with React components

== Changelog ==
= 0.1.1 =
* Initial release

== License ==
GPL v2 or later. See https://www.gnu.org/licenses/gpl-2.0.html for details.
