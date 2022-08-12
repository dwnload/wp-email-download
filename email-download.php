<?php
/**
 * Plugin Name: Email Download
 * Plugin URI: https://github.com/dwnload/wp-email-download
 * Description: Allow users to download any WordPress managed file if they're subscribed to you MailChimp list.
 * Version: 0.6.2
 * Requires PHP: 7.4
 * Author:  Austin Passy
 * Author URI: https://austin.passy.co
 * Text Domain: email-download
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/dwnload/wp-email-download
 * Primary Branch: master
 * Release Asset: true
 *
 * Copyright (c) 2019 - 2022 Passy.co, LLC (https://passy.co/)
 */

use Dwnload\WpEmailDownload\EmailDownload;

if (version_compare(phpversion(), '7.4', '>=')) {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    EmailDownload::setFile(__FILE__);
    (new EmailDownload())->hookup();
} else {
    require __DIR__ . '/src/includes/functions.php';
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI') ) {
        WP_CLI::warning(\Dwnload\WpEmailDownload\php_version_text());
    } else {
        add_action('admin_notices', '\Dwnload\WpEmailDownload\version_error');
    }
}
