<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Http;

use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use WP_REST_Response;

/**
 * Class RestResponse
 * @package Dwnload\WpEmailDownload\Http
 */
class RestResponse implements WpHooksInterface
{

    public function addHooks(): void
    {
        add_filter('rest_prepare_post', [$this, 'modifyPostsResponse'], 10, 1);
    }

    /**
     * Extend the return data to allow select2 to properly get the post titles.
     * @param WP_REST_Response $response
     * @return WP_REST_Response $data
     */
    public function modifyPostsResponse(WP_REST_Response $response): WP_REST_Response
    {
        $data = $response->get_data();
        $data['text'] = $data['title']['rendered'];
        $data['post_title'] = $data['title']['rendered'];

        $response->set_data($data);

        return $data;
    }
}
