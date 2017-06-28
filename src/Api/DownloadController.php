<?php

namespace Dwnload\WpEmailDownload\Api;

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

    const ROUTE_NAMESPACE = 'dwnload/v1';
    const ROUTE_REQUIRED_FIELD = 'email';

    /**
     * Registers a REST API route.
     */
    public function initializeRoute() {
        $this->registerRoute(
            self::ROUTE_NAMESPACE,
            "/user/(?P<" . self::ROUTE_REQUIRED_FIELD . ">\S+)",
            [ $this, 'index' ],
            [
                'args' => [
                    self::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function( $value ): bool {
                            $is_valid_email = ( new EmailValidator() )->isValid( $value, new MultipleValidationWithAnd( [
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
     * @return WP_REST_Response
     */
    public function index( WP_REST_Request $request ): WP_REST_Response {
        $data = [
            'access' => false,
            'date' => date( 'Y-m-d H:i:s' ),
        ];

        $params = $request->get_params();
        if ( empty( $params[ self::ROUTE_REQUIRED_FIELD ] ) ||
            empty( $params[ Mailchimp::LIST_ID ] )
        ) {
            return new WP_REST_Response( $data, \WP_Http::OK );
        }

        $chimp = new MailChimp( '1787e8cd321826254707222278847628-us6' );
        $list_id = $params[ Mailchimp::LIST_ID ];
        $subscriber_hash = $chimp->subscriberHash( sanitize_email( $params[ self::ROUTE_REQUIRED_FIELD ] ) );
        $response = $chimp->get( "lists/$list_id/members/$subscriber_hash" );

        // User is subscribed, send them the download!
        if ( $chimp->success() && isset( $response['id'] ) ) {
            $data['access'] = true;
        }

        return new WP_REST_Response( $data, \WP_Http::OK );
    }
}
