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

    const NONCE_ACTION = 'wp_rest';
    const ROUTE_NAMESPACE = 'dwnload/v1';
    const ROUTE_ROUTE_PREFIX = '/user/';
    const ROUTE_REQUIRED_FIELD = 'email';
    const SCRIPT_HANDLE = 'email-download';
    const OBJECT_NAME = 'emailDownload';
    const DOWNLOAD_KEY = 'file_id';

    const ENCRYPTION_DELIMITER = '|';
    const ENCRYPTION_KEY = 'EMa1LD0WnL08D' . self::ENCRYPTION_DELIMITER;
    const MAX_SUBMISSIONS = 5;

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
     * Add class hooks.
     */
    public function addHooks() {
        parent::addHooks();
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
                'namespace' => self::ROUTE_NAMESPACE,
                'route' => self::ROUTE_ROUTE_PREFIX,
                'nonce' => wp_create_nonce( self::NONCE_ACTION ),
                'success' => __( 'Thanks for your submission!', 'your-text-domain' ),
                'failure' => __( 'Your submission could not be processed.', 'your-text-domain' ),
                'current_user_id' => get_current_user_id(),
            ]
        );
    }

    /**
     * Registers a REST API route.
     */
    public function initializeRoute() {
        $this->registerRoute(
            self::ROUTE_NAMESPACE,
            self::ROUTE_ROUTE_PREFIX . "(?P<" . self::ROUTE_REQUIRED_FIELD . ">\S+)",
            [ $this, 'validateUserEmailSubscription' ],
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
    public function validateUserEmailSubscription( WP_REST_Request $request ) {
        if ( ! check_ajax_referer( self::NONCE_ACTION, false, false ) ) {
            return rest_ensure_response( new \WP_Error(
                'nonce_error',
                'A valid nonce key is required, please try again.',
                [ 'status' => \WP_Http::UNAUTHORIZED ]
            ) );
        }

        $data = [
            'success' => false,
            'date' => date( 'Y-m-d H:i:s' ),
        ];

        // Required parameters (though the 'email' field is required by the route
        if ( empty( $request->get_param( self::ROUTE_REQUIRED_FIELD ) ) ||
            empty( $request->get_param( Mailchimp::LIST_ID ) )
        ) {
            return rest_ensure_response( new \WP_Error(
                'missing_params',
                'One or more parameters are missing from this request.',
                [ 'status' => \WP_Http::OK ]
            ) );
        }

        // This is here for extra protection (not for users) Admins show have their keys set
        if ( empty( $api_key = $this->settings->getSettings()[ Mailchimp::SETTING_API_KEY ] ) ) {
            return rest_ensure_response( new \WP_Error(
                'missing_api_key',
                'A MailChimp API Key is required to complete this request.',
                [ 'status' => \WP_Http::OK ]
            ) );
        }

        // Count submissions
        if ( ! $this->canSubmitForm( time() ) ) {
            return rest_ensure_response( new \WP_Error(
                'submission_error',
                'Form submission exceeded. Please try again in an hour.',
                [ 'status' => \WP_Http::OK ]
            ) );
        }

        try {
            $chimp = new MailChimp( $api_key );
            $list_id = $this->decrypt( $request->get_param( Mailchimp::LIST_ID ) );
            $subscriber = $chimp->subscriberHash( $request->get_param( self::ROUTE_REQUIRED_FIELD ) );
            $response = $chimp->get( "lists/$list_id/members/$subscriber" );

            // User is subscribed, send them the download!
            if ( $chimp->success() && isset( $response['id'] ) ) {
                $file = $this->decryptFileAndDownload( $request );
                if ( $file !== '' ) {
                    $data['success'] = true;
                    $data['file'] = $file;
                }
                delete_transient( $this->getTransientKey() );
            }
        } catch ( \Exception $e ) {
            $data['error'] = esc_html( $e->getMessage() );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Decrypt a string.
     *
     * @param string $data
     * @param string $encryption_key
     *
     * @return string
     */
    public function decrypt( string $data, string $encryption_key = self::ENCRYPTION_KEY ) {
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $encryption_key );
        $iv = substr( hash( 'sha256', sprintf( '%s_iv',$encryption_key ) ), 0, 16 );

        return openssl_decrypt( base64_decode( $data ), $encrypt_method, $key, 0, $iv );
    }

    /**
     * Encrypt a string.
     *
     * @param string $data
     * @param string $encryption_key
     *
     * @return string
     */
    public static function encrypt( string $data, string $encryption_key = self::ENCRYPTION_KEY ) {
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $encryption_key );
        $iv = substr( hash( 'sha256', sprintf( '%s_iv',$encryption_key ) ), 0, 16 );

        return base64_encode( openssl_encrypt( $data, $encrypt_method, $key, 0, $iv ) );
    }

    /**
     * @link https://stackoverflow.com/a/18187783/558561
     * @return string
     */
    public static function getComputerId(): string {
        static $computer_id;

        if ( ! $computer_id ) {
            $computer_id = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $computer_id .= isset( $_SERVER['LOCAL_ADDR'] ) ? $_SERVER['LOCAL_ADDR'] : '';
            $computer_id .= isset( $_SERVER['LOCAL_PORT'] ) ? $_SERVER['LOCAL_PORT'] : '';
            $computer_id .= isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        }

        return $computer_id;
    }

    /**
     * Get the attachment URI.
     *
     * @param WP_REST_Request $request
     *
     * @return string Returns URI to uploaded attachment or empty string on failure.
     */
    protected function decryptFileAndDownload( WP_REST_Request $request ): string {
        $file_id = $this->decrypt( $request->get_param( self::DOWNLOAD_KEY ), self::getComputerId() );
        $uri = wp_get_attachment_url( absint( $file_id ) );

        return is_string( $uri ) ? $uri : '';
    }

    /**
     * Can the user submit the form request?
     *
     * @param int $time
     *
     * @return bool
     */
    private function canSubmitForm( int $time ): bool {
        $transient = $this->getTransientKey();
        $session = get_transient( $transient );
        if ( $session === false ) {
            $session = [
                'last_submitted' => 0,
                'submission_count' => 0,
            ];
        }

        if ( $session['submission_count'] > self::MAX_SUBMISSIONS ||
            (
                $time - $session['last_submitted'] < HOUR_IN_SECONDS &&
                $session['submission_count'] > self::MAX_SUBMISSIONS
            )
        ) {
            return false;
        }

        $session['last_submitted'] = $time;
        $session['submission_count'] = $session['submission_count'] + 1;

        set_transient( $transient, $session, DAY_IN_SECONDS );

        return true;
    }

    /**
     * Get the transient key.
     *
     * @return string
     */
    private function getTransientKey(): string {
        return sprintf( 'email_download_%s', md5( self::getComputerId() ) );
    }
}
