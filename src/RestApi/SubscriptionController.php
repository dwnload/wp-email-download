<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\RestApi;

use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\ApiFactory;
use Dwnload\WpEmailDownload\Api\MailChimp;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpSettingsApi\Api\Options;
use Exception;
use TheFrosty\WpUtilities\RestApi\Http\RegisterPostRoute;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function check_ajax_referer;
use function esc_attr;
use function esc_html;
use function wp_doing_ajax;
use function wp_is_rest_endpoint;
use function wp_verify_nonce;

/**
 * Class SubscriptionController
 * @package Dwnload\WpEmailDownload\Api
 */
class SubscriptionController extends RegisterPostRoute
{

    use ApiFactory;

    const string ROUTE_PREFIX = '/user/';
    const string ROUTE_REQUIRED_FIELD = 'email';
    const string DOWNLOAD_KEY = 'file_id';

    /**
     * Registers a REST API route.
     * @param WP_REST_Server $server
     * @todo add permission_callback to $args param of registerRoute.
     */
    public function initializeRoute(WP_REST_Server $server): void
    {
        $this->registerRoute(
            EmailDownload::ROUTE_NAMESPACE,
            self::ROUTE_PREFIX . "(?P<" . self::ROUTE_REQUIRED_FIELD . ">\S+)",
            [$this, 'validateUserEmailSubscription'],
            [
                'args' => [
                    self::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_email',
                        'validate_callback' => function ($value): bool {
                            return $this->api->isValidEmail($value);
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Parse the rest request and return our response(s).
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function validateUserEmailSubscription(WP_REST_Request $request): WP_Error|WP_REST_Response
    {
        if (
            (wp_doing_ajax() && !check_ajax_referer('wp_rest', false, false)) ||
            (wp_is_rest_endpoint() && !wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest'))
        ) {
            return rest_ensure_response(
                new WP_Error(
                    'nonce_error',
                    'A valid nonce key is required, please try again.',
                    ['status' => WP_Http::UNAUTHORIZED]
                )
            );
        }

        $data = [
            'success' => false,
            'date' => date('Y-m-d H:i:s'),
        ];

        // Required parameters (though the 'email' field is required by the route).
        if (
            empty($request->get_param(self::ROUTE_REQUIRED_FIELD)) ||
            empty($request->get_param(Mailchimp::LIST_ID))
        ) {
            return rest_ensure_response(
                new WP_Error(
                    'missing_params',
                    'One or more parameters are missing from this request.',
                    ['status' => WP_Http::OK]
                )
            );
        }

        // This is here for extra protection (not for users) Admins should have their keys set!
        if (empty($api_key = Options::getOption(Mailchimp::SETTING_API_KEY))) {
            return rest_ensure_response(
                new WP_Error(
                    'missing_api_key',
                    'A MailChimp API Key is required to complete this request.',
                    ['status' => WP_Http::OK]
                )
            );
        }

        // Count submissions.
        if (!$this->canSubmitForm(time())) {
            return rest_ensure_response(
                new WP_Error(
                    'submission_error',
                    'Form submission exceeded. Please try again in an hour.',
                    ['status' => WP_Http::OK]
                )
            );
        }

        try {
            $chimp = new MailChimp($api_key);
            $list_id = $this->api->decrypt($request->get_param(Mailchimp::LIST_ID));
            $email_address = sanitize_email($request->get_param(self::ROUTE_REQUIRED_FIELD));
            $subscriber = $chimp->subscriberHash($email_address);
            $response = $chimp->get("lists/$list_id/members/$subscriber");

            // User is subscribed, send them the download!
            if ($chimp->success() && isset($response['id'])) {
                $file_url = $this->api->getDecryptFileIdAttachmentUrl($request);
                if ($file_url !== '') {
                    $data['success'] = true;
                    $data['url'] = $this->api->buildDownloadRestUrl(
                        $email_address,
                        $subscriber,
                        $file_url
                    );
                }
                delete_transient($this->api->getTransientKey());
            } else {
                // @todo Make this an option from the settings page.
                $data['message'] = 'It seems you\'re not subscribed.';
            }
        } catch (Exception $e) {
            $data['code'] = esc_attr($e->getCode());
            $data['message'] = esc_html($e->getMessage());
        }

        return rest_ensure_response($data);
    }

    /**
     * Can the user submit the form request?
     * @param int $time
     * @return bool
     */
    private function canSubmitForm(int $time): bool
    {
        $key = $this->api->getTransientKey();
        $transient = get_transient($key);
        if ($transient === false) {
            $transient = [
                'last_submitted' => 0,
                'submission_count' => 0,
            ];
        }

        if ($transient['submission_count'] > Api::MAX_SUBMISSIONS ||
            (
                $time - $transient['last_submitted'] < HOUR_IN_SECONDS &&
                $transient['submission_count'] > Api::MAX_SUBMISSIONS
            )
        ) {
            return false;
        }

        $transient['last_submitted'] = $time;
        $transient['submission_count'] = $transient['submission_count'] + 1;

        set_transient($key, $transient, DAY_IN_SECONDS);

        return true;
    }
}
