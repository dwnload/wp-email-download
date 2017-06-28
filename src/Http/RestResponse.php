<?php

namespace Dwnload\WpEmailDownload\Http;

use Dwnload\WpEmailDownload\WpHooksInterface;

/**
 * Class RestResponse
 *
 * @package Dwnload\WpEmailDownload\Classes\Http
 */
class RestResponse implements WpHooksInterface {

    public function addHooks() {
        add_filter( 'rest_prepare_post', [ $this, 'modifyPostsResponse' ], 10, 1 );
    }

    /**
     * Extend the return data to allow select2 to properly get the post titles.
     *
     * @param mixed $data
     *
     * @return mixed $data
     */
    public function modifyPostsResponse( $data ) {
        $_data = $data->data;
        $_data['text'] = $_data['title']['rendered'];
        $_data['post_title'] = $_data['title']['rendered'];

        $data->data = $_data;

        return $data;
    }
}
