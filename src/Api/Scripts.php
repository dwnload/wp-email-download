<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\EmailDownload;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use function filemtime;
use function plugin_dir_path;
use function plugins_url;
use function wp_register_style;

/**
 * Class Scripts
 * @package Dwnload\WpEmailDownload\Api
 */
class Scripts implements WpHooksInterface
{

    const string SCRIPT_HANDLE = 'email-download';
    const string OBJECT_NAME = 'emailDownload';

    /**
     * Add class hooks
     */
    public function addHooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'registerScripts']);
    }

    /**
     * Register Api scripts.
     */
    public function registerScripts(): void
    {
        wp_register_style(
            self::SCRIPT_HANDLE,
            plugins_url('assets/css/style.css', EmailDownload::getFile()),
            ver: filemtime(plugin_dir_path(EmailDownload::getFile()) . 'assets/css/style.css')
        );
        wp_register_script(
            self::SCRIPT_HANDLE,
            plugins_url('assets/js/email-download.js', EmailDownload::getFile()),
            ['jquery'],
            ver: filemtime(plugin_dir_path(EmailDownload::getFile()) . 'assets/js/email-download.js')
        );

        wp_localize_script(self::SCRIPT_HANDLE, self::OBJECT_NAME, [
                'root' => esc_url_raw(rest_url()),
                'namespace' => EmailDownload::ROUTE_NAMESPACE,
                'route' => SubscriptionController::ROUTE_PREFIX,
                'nonce' => wp_create_nonce('wp_rest'),
                'success' => __('Thanks for your submission!', 'your-text-domain'),
                'failure' => __('Your submission could not be processed.', 'your-text-domain'),
                'current_user_id' => get_current_user_id(),
            ]
        );
    }
}
