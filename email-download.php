<?php
/**
 * Plugin Name: Email Download
 * Plugin URI: https://github.com/dwnload/wp-email-download
 * Description: ...
 * Version: 0.1
 * Author:  Austin Passy
 * Author URI: https://austin.passy.co
 * Text Domain: email-download
 * Domain Path: /languages
 *
 * Copyright (c) 2017 Passy.co, LLC (https://passy.co/)
 */

if ( version_compare( phpversion(), '7.0.1', '>=' ) ) {
    // If we haven't loaded this plugin from Composer we need to add our own autoloader
    if ( ! class_exists( 'Dwnload\WpEmailDownload\EmailDownload' ) ) {
        $autoloader = require_once 'autoload.php';
        $autoloader( 'Dwnload\\WpEmailDownload\\', __DIR__ . '/src/' );
    }

    add_action( 'plugins_loaded', [ ( new \Dwnload\WpEmailDownload\EmailDownload() ), 'addHooks' ] );
    register_activation_hook( __FILE__, '\Dwnload\WpEmailDownload\EmailDownload::activationHook' );
} else {
    if ( class_exists( 'WP_CLI' ) && defined( 'WP_CLI' ) ) {
        WP_CLI::warning( _dwnload_email_download_version_text() );
    } else {
        add_action( 'admin_notices', '_dwnload_email_download_version_error' );
    }
}

/**
 * Admin notice for incompatible versions of PHP.
 */
function _dwnload_email_download_version_error() {
    printf( '<div class="error"><p>%s</p></div>', esc_html( _dwnload_email_download_version_text() ) );
}

/**
 * String describing the minimum PHP version.
 *
 * @return string
 */
function _dwnload_email_download_version_text() {
    return __( 'Email Download plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 7.0 or higher.', 'email-download' );
}
