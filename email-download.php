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
    require __DIR__ . '/vendor/autoload.php';

    ( new \Dwnload\WpEmailDownload\EmailDownload(
        new \Dwnload\WpEmailDownload\Init(),
        __FILE__
    ) )->hookup();
    register_activation_hook( __FILE__, '\Dwnload\WpEmailDownload\EmailDownload::activationHook' );
} else {
    require __DIR__ . '/src/includes/functions.php';
    if ( class_exists( 'WP_CLI' ) && defined( 'WP_CLI' ) ) {
        WP_CLI::warning( \Dwnload\WpEmailDownload\php_version_text() );
    } else {
        add_action( 'admin_notices', '\Dwnload\WpEmailDownload\version_error' );
    }
}
