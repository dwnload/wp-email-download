<?php

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\Http\Services\RegisterGetRoute;
use WP_REST_Request;

class DownloadController extends RegisterGetRoute {

    const ENCRYPTION_KEY = 'D0WnL0ADk3Y';
    const ROUTE_FILE_PREFIX = '/download/';
    const ROUTE_NONCE_REQUIRED_FIELD = 'nonce';

    /** @var Api $api */
    protected $api;

    /** @var Settings $settings */
    protected $settings;

    /**
     * DownloadController constructor.
     *
     * @param Api $api
     * @param Settings $settings
     */
    public function __construct( Api $api, Settings $settings ) {
        $this->api = $api;
        $this->settings = $settings;
    }

    /**
     * Registers a REST API route.
     */
    public function initializeRoute() {
        $this->registerRoute(
            EmailDownload::ROUTE_NAMESPACE,
            self::ROUTE_FILE_PREFIX . "(?P<" . self::ROUTE_NONCE_REQUIRED_FIELD . ">\S+)" . '/' .
            "(?P<" . SubscriptionController::ROUTE_REQUIRED_FIELD . ">\S+)",
            [ $this, 'validateDownloadableFile' ],
            [
                'args' => [
                    self::ROUTE_NONCE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function( $value, $request, $key ): bool {
                            $email_address = $this->api->decrypt( $value, self::ENCRYPTION_KEY );
                            error_log( json_encode( $email_address ) );
                            $cookie = ''; // GET THE COOKIE OR SESSION VALUE HERE
                            return $email_address == $cookie && $this->api->isValidEmail( $email_address );

                            return true;
                        },
                    ],
                    SubscriptionController::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function( $value ): bool {
                            return $this->api->isValidEmail( $value );
                        },
                    ],
                ],
            ]
        );
    }

    public function validateDownloadableFile( WP_REST_Request $request ) {
        error_log( json_encode( $request ) );

        return rest_ensure_response( [] );
    }
}
