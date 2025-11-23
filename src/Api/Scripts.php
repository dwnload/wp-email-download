<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\Blocks\EmailDownload as EmailDownloadBlock;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\RestApi\SubscriptionController;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use WP_Block_Type_Registry;
use function __;
use function esc_url_raw;
use function filemtime;
use function get_current_user_id;
use function plugin_dir_path;
use function plugins_url;
use function rest_url;
use function wp_create_nonce;
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

        // Localize our script data to each registered script (our shortcode & block).
        $handles = [self::SCRIPT_HANDLE];
        $registry = WP_Block_Type_Registry::get_instance();
        if (
            $registry &&
            $block = $registry->get_all_registered()[EmailDownloadBlock::NAME] ?? null
        ) {
            $handles = array_merge($handles, $block->view_script_handles);
        }
        foreach ($handles as $handle) {
            wp_localize_script(
                $handle,
                self::OBJECT_NAME,
                [
                    'root' => esc_url_raw(rest_url()),
                    'namespace' => EmailDownload::ROUTE_NAMESPACE,
                    'route' => SubscriptionController::ROUTE_PREFIX,
                    'nonce' => wp_create_nonce('wp_rest'),
                    'success' => __('Thanks for your submission!', 'email-download'),
                    'failure' => __('Your submission could not be processed.', 'email-download'),
                    'current_user_id' => get_current_user_id(),
                ]
            );
        }
    }
}
