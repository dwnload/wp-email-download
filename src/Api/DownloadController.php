<?php

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\Http\Services\RegisterPostRoute;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class DownloadController
 *
 * @package Dwnload\WpEmailDownload\Api
 */
class DownloadController extends RegisterPostRoute {

    const ROUTE_REQUIRED_FIELD = 'email';

    /** @var  Settings $settings */
    protected $settings;

    /**
     * DownloadController constructor.
     *
     * @param Settings $settings
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Registers a REST API route.
     */
    public function initializeRoute() {
        $this->registerRoute(
            EmailDownload::ROUTE_NAMESPACE,
            "/user/(?P<" . self::ROUTE_REQUIRED_FIELD . ">\S+)",
            [ $this, 'index' ],
            [
                'args' => [
                    self::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function( $value ): bool {
                            $is_valid_email = ( new EmailValidator() )
                                ->isValid( $value, new MultipleValidationWithAnd( [
                                    new RFCValidation(),
                                    new DNSCheckValidation(),
                                ] ) );

                            return $is_valid_email;
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|mixed
     */
    public function index( WP_REST_Request $request ) {
        $data = [
            'access' => false,
            'date' => date( 'Y-m-d H:i:s' ),
        ];

        // Required parameters (though the 'email' field is required by the route
        if ( empty( $request->get_param( self::ROUTE_REQUIRED_FIELD ) ) ||
             empty( $request->get_param( Mailchimp::LIST_ID ) )
        ) {
            return rest_ensure_response( new \WP_Error(
                'missing_params',
                'One or more parameters are missing from this request.'
            ) );
        }

        if ( empty( $api_key = $this->settings->getSetting( Mailchimp::SETTING_API_KEY ) ) ) {
            return rest_ensure_response( new \WP_Error(
                'missing_api_key',
                'A MailChimp API Key is required to complete this request.'
            ) );
        }

        try {
            $chimp = new MailChimp( $api_key );
            $list_id = $request->get_param( Mailchimp::LIST_ID );
            $subscriber = $chimp->subscriberHash( $request->get_param( self::ROUTE_REQUIRED_FIELD ) );
            $response = $chimp->get( "lists/$list_id/members/$subscriber" );

            // User is subscribed, send them the download!
            if ( $chimp->success() && isset( $response['id'] ) ) {
                $data['access'] = true;
            }
        } catch ( \Exception $e ) {
            $data['error'] = esc_html( $e->getMessage() );
        }

        return rest_ensure_response( $data );
    }
}
