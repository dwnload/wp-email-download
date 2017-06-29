<?php

namespace Dwnload\WpEmailDownload;

/**
 * Helper function to return admin notice HTML.
 *
 * @param string $message
 * @param string $class
 *
 */
function admin_notice( string $message, string $class = 'error' ) {
    printf(
        '<div class="%s"><p>%s</p></div>',
        sanitize_html_class( $class ),
        esc_html( $message )
    );
}

/**
 * Admin notice for incompatible versions of PHP.
 */
function version_error() {
    admin_notice( php_version_text(), 'error' );
}

/**
 * String describing the minimum PHP version.
 *
 * @return string
 */
function php_version_text() {
    return esc_html__( 'Email Download plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 7.0 or higher.', 'email-download' );
}