# WP Backup

A powerful, user-friendly WordPress plugin to backup, restore, import, and export your entire site—including database, plugins, themes, uploads, and demo content. Built for reliability, speed, and ease of use.

![WP Backup Preview](https://github.com/miketropi/worry-proof-backup/blob/master/assets/screenshot-1.jpg?raw=true)
![WP Backup Preview](https://github.com/miketropi/worry-proof-backup/blob/master/assets/screenshot-2.jpg?raw=true)
![WP Backup Preview](https://github.com/miketropi/worry-proof-backup/blob/master/assets/screenshot-3.jpg?raw=true)
![WP Backup Preview](https://github.com/miketropi/worry-proof-backup/blob/master/assets/screenshot-4.jpg?raw=true)

## Features

- **One-Click Backup & Restore**: Effortlessly create and restore backups of your WordPress site.
- **Database Import/Export**: Backup, export, and restore your WordPress database with ease.
- **Plugin & Theme Backup**: Export and import all plugins and themes, or restore them from a backup.
- **Uploads & Folder Backup**: Securely backup and restore your `uploads` folder and other important directories.
- **Demo Content Import**: Provide a seamless demo content import solution for theme and plugin developers.
- **Modern Admin UI**: Clean, React-powered admin interface for managing backups and server metrics.
- **AJAX & Nonce Security**: All operations are secured with WordPress nonces and AJAX for a smooth experience.
- **Automated Backup Scheduling**: Set up daily, weekly, or monthly automated backups with email notifications.
- **Email Notifications**: Receive detailed email reports when scheduled backups complete successfully.
- **Backup Status Tracking**: Monitor backup completion status and history through the admin interface.

## 📦 Upload Backups via CLI

Starting with version 0.2.0, WP Backup supports **chunked upload of large backup ZIP files directly via the command line** using a REST API endpoint, perfect for big sites or automated workflows.

- [CLI Tool & Usage Guide →](https://github.com/miketropi/wpb-upload-cli)

This feature enables you to:
- Upload and restore backup ZIPs larger than typical browser/APIs allow.
- Integrate with the open source CLI client [`wpb-upload-cli`](https://github.com/miketropi/wpb-upload-cli).

**How it works:**
1. Generates an upload session for the backup ZIP, sending it in chunks to avoid timeouts or browser limits.
2. After upload completes, triggers the restore process via a REST API endpoint.

**Quick Start:**
- See [`wpb-upload-cli`](https://github.com/miketropi/wpb-upload-cli) for command-line usage instructions, authentication, and automation tips.

**Security:** Only admins (users with the `manage_options` capability) can use this REST API/CLI upload feature.


## Installation

1. Upload the plugin files to the `/wp-content/plugins/worry-proof-backup` directory, or install via the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **WP Backup** menu in your WordPress admin sidebar.

## Usage

- **Create a Backup**: Click 'Create Backup', select what to include (database, plugins, themes, uploads), and confirm.
- **Restore a Backup**: Choose a backup from the list and click 'Restore'.
- **Import/Export**: Use the import/export tools to move your site or demo content between environments.
- **Demo Content**: Theme/plugin authors can bundle demo content for easy import by users.

## Demo Content Import Solution

WP Backup provides a developer-friendly way to package and import demo content, making it ideal for theme and plugin authors who want to offer a quick start for their users.

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

---

## ⚠️ PHP 8.0 or Higher Required – Why?

**Worry Proof Backup** is built with modern techniques to ensure your data **restores safely, quickly, and reliably** – especially when working with large backup files.

We require **PHP 8.0 or higher** for the following reasons:

### 🚀 1. Superior Performance

PHP 8 introduces **Just-In-Time (JIT)** compilation, which significantly speeds up operations like:

* Extracting ZIP archives with tens of thousands of files
* Reading and writing large file contents in memory
* Fast, stable looping over file lists

### 🔒 2. Improved Stability and Accuracy

In PHP 7, some built-in functions like `stream_get_contents()` can behave inconsistently with large files or streams. PHP 8 provides:

* More reliable file extraction
* Fewer write failures and timeouts
* Better handling of memory streams

### 🧠 3. Better File System Handling

Our plugin supports restoring entire folders with **thousands of files**, which demands fast and reliable filesystem operations – something PHP 8 handles far better than PHP 7.

---

### 💡 Still Using PHP 7?

We strongly recommend upgrading to **PHP 8.0+** for optimal compatibility, performance, and security – not just for this plugin, but for your entire WordPress site.

Most hosting providers support PHP 8 – feel free to reach out to your hosting support team and ask for an upgrade.

---

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

**Developed by @Mike**
