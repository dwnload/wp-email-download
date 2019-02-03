<?php

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\Http\Services\RouteService;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class Scripts
 *
 * @package Dwnload\WpEmailDownload\Api
 */
class Scripts implements WpHooksInterface {

    const SCRIPT_HANDLE = 'email-download';
    const OBJECT_NAME = 'emailDownload';

    /**
     * Add class hooks
     */
    public function addHooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'registerScripts' ] );
    }

    /**
     * Register Api scripts.
     */
    public function registerScripts() {
        wp_register_script(
            self::SCRIPT_HANDLE,
            plugins_url( 'assets/js/email-download.js', EmailDownload::getFile() ),
            [ 'jquery' ]
        );

        wp_localize_script( self::SCRIPT_HANDLE, self::OBJECT_NAME, [
                'root' => esc_url_raw( rest_url() ),
                'namespace' => EmailDownload::ROUTE_NAMESPACE,
                'route' => SubscriptionController::ROUTE_PREFIX,
                'nonce' => wp_create_nonce( RouteService::NONCE_ACTION ),
                'success' => __( 'Thanks for your submission!', 'your-text-domain' ),
                'failure' => __( 'Your submission could not be processed.', 'your-text-domain' ),
                'current_user_id' => get_current_user_id(),
            ]
        );
    }
}
