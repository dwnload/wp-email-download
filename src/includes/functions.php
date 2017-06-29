<?php

namespace Dwnload\WpEmailDownload;

const PLUGIN_NAME = 'Email Download';
const SHORTCODE_UI_SLUG = 'shortcode-ui';

/**
 * Helper function to return admin notice HTML.
 *
 * @param string $message
 * @param string $class
 *
 */
function admin_notice( string $message, string $class = 'error' ) {
    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        sanitize_html_class( $class ),
        $message
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
    return sprintf(
        esc_html__( '%s plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 7.0 or higher.', 'email-download' ),
        PLUGIN_NAME
    );
}

/**
 * String advising the install of a required plugin.
 *
 * @return string
 */
function missing_shorcode_ui_text() {
    if ( current_user_can( 'install_plugins' ) ) {
        $plugin_slug = 'shortcode-ui';
        $install_url = wp_nonce_url(
            self_admin_url( 'update.php?action=install-plugin&plugin=' . SHORTCODE_UI_SLUG ),
            'install-plugin_' . SHORTCODE_UI_SLUG
        );
        $details_url = self_admin_url(
            'plugin-install.php?tab=plugin-information&amp;plugin=' . SHORTCODE_UI_SLUG . '&amp;TB_iframe=true&amp;width=600&amp;height=550'
        );

        return sprintf(
            __( '%s plugin error: The Shorcode UI plugin is required. View the plugin <a href="%s" class="thickbox open-plugin-details-modal">details</a> or <a href="%s">install it now</a>.', 'email-download' ),
            PLUGIN_NAME,
            esc_url( $details_url ),
            esc_url( $install_url )
        );
    } else {
        return sprintf(
            __( '%s plugin error: The Shorcode UI plugin is required.', 'email-download' ),
            PLUGIN_NAME
        );
    }
}