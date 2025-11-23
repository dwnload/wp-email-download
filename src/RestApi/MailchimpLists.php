<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\RestApi;

use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpSettingsApi\Api\Options;
use Exception;
use TheFrosty\WpUtilities\RestApi\Http\RegisterGetRoute;
use WP_Error;
use WP_Http;
use WP_REST_Response;
use WP_REST_Server;
use function current_user_can;
use function rest_ensure_response;

/**
 * Class MailchimpLists
 * @package Dwnload\WpEmailDownload\Api
 */
class MailchimpLists extends RegisterGetRoute
{

    const string ROUTE_PREFIX = '/lists';

    /**
     * Registers a REST API route.
     * @param WP_REST_Server $server
     * @todo add permission_callback to $args param of registerRoute.
     */
    public function initializeRoute(WP_REST_Server $server): void
    {
        $this->registerRoute(
            EmailDownload::ROUTE_NAMESPACE,
            self::ROUTE_PREFIX,
            [$this, 'getLists'],
            [
                self::ARG_PERMISSION_CALLBACK => static fn(): bool => current_user_can('edit_posts'),
            ]
        );
    }

    /**
     * Get Mailchimp lists.
     * @return WP_REST_Response
     */
    public function getLists(): WP_REST_Response
    {
        $api_key = Options::getOption(Mailchimp::SETTING_API_KEY);
        if (empty($api_key)) {
            return rest_ensure_response(
                new WP_Error(
                    'empty_api_key',
                    "Please enter a valid Mailchimp API key.",
                    ['status' => WP_Http::OK]
                )
            );
        }

        try {
            $lists = (new MailChimp($api_key))->getListsJson();
        } catch (Exception $e) {
            return rest_ensure_response(
                new WP_Error(
                    'get_lists_error',
                    $e->getMessage(),
                    ['status' => WP_Http::OK]
                )
            );
        }

        return new WP_REST_Response($lists, WP_Http::OK);
    }
}
