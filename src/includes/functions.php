<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload;

use function __;
use function current_user_can;
use function esc_html__;
use function esc_url;
use function get_user_option;
use function printf;
use function sanitize_html_class;
use function self_admin_url;
use function sprintf;
use function wp_nonce_url;

const PLUGIN_NAME = 'Email Download';
const SHORTCODE_UI_SLUG = 'shortcode-ui';

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * Helper function to return admin notice HTML.
 * @param string $message
 * @param string $class
 */
function admin_notice(string $message, string $class = 'error'): void
{
    if (filter_var(get_user_option('dismissed_wp_email_download_notice', false), FILTER_VALIDATE_BOOLEAN)) {
        return;
    }
    $nonce = wp_create_nonce(SHORTCODE_UI_SLUG);
    $user_id = get_current_user_id();
    $script = <<<JS
        (function ($) {
          $(function () {
            $('div[data-dismissible]').on('click', '.notice-dismiss', function (event) {
                event.preventDefault()
                $.post(ajaxurl, {
                  action: 'wped_dismiss_admin_notice',
                  user_id: $user_id,
                  nonce: '$nonce'
                })
                $(this).closest('div[data-dismissible]').hide('slow')
              }
            )
          })
        }(jQuery))
        JS;

    printf(
        '<div data-dismissible="" class="notice notice-%s is-dismissible"><p>%s</p><script>%s</script></div>',
        sanitize_html_class($class),
        $message,
        $script // phpcs:ignore
    );
}

/**
 * Admin notice for incompatible versions of PHP.
 */
function version_error(): void
{
    admin_notice(php_version_text());
}

/**
 * String describing the minimum PHP version.
 * @return string
 */
function php_version_text(): string
{
    return sprintf(
        esc_html__(
            '%s plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 8.0 or higher.',
            'email-download'
        ),
        PLUGIN_NAME
    );
}

/**
 * String advising the installation of a required plugin.
 * @return string
 */
function missing_shorcode_ui_text(): string
{
    if (current_user_can('install_plugins')) {
        $install_url = wp_nonce_url(
            self_admin_url('update.php?action=install-plugin&plugin=' . SHORTCODE_UI_SLUG),
            'install-plugin_' . SHORTCODE_UI_SLUG
        );
        $details_url = self_admin_url(
            'plugin-install.php?tab=plugin-information&amp;plugin=' . SHORTCODE_UI_SLUG . '&amp;TB_iframe=true&amp;width=600&amp;height=550'
        );

        return sprintf(
            __(
                '%1$s plugin warning: The Shorcode UI plugin is suggested. View the plugin <a href="%2$s" class="thickbox open-plugin-details-modal">details</a> or <a href="%3$s">install it now</a>.',
                'email-download'
            ),
            PLUGIN_NAME,
            esc_url($details_url),
            esc_url($install_url)
        );
    }

    return sprintf(
        __('%s plugin warning: The Shorcode UI plugin is suggested.', 'email-download'),
        PLUGIN_NAME
    );
}
