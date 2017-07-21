<?php
/**
 * Plugin Name: Email Download
 * Plugin URI: https://github.com/dwnload/wp-email-download
 * Description:Allow users to download any WordPress managed file if they're subscribed to you MailChimp list.
 * Version: 0.2.2
 * Author:  Austin Passy
 * Author URI: https://austin.passy.co
 * Text Domain: email-download
 * Domain Path: /languages
 *
 * Copyright (c) 2017 Passy.co, LLC (https://passy.co/)
 */

use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\Init;

if ( version_compare( phpversion(), '7.0.1', '>=' ) ) {
    require __DIR__ . '/vendor/autoload.php';

    ( new EmailDownload( new Init(), __FILE__ ) )->hookup();
} else {
    require __DIR__ . '/src/includes/functions.php';
    if ( class_exists( 'WP_CLI' ) && defined( 'WP_CLI' ) ) {
        WP_CLI::warning( \Dwnload\WpEmailDownload\php_version_text() );
    } else {
        add_action( 'admin_notices', '\Dwnload\WpEmailDownload\version_error' );
    }
}
