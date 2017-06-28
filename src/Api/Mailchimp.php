<?php

namespace Dwnload\WpEmailDownload\Api;

/**
 * Class Mailchimp
 *
 * @package Dwnload\WpEmailDownload\Api
 */
class Mailchimp extends \DrewM\MailChimp\MailChimp {

    const LIST_ID = 'list_id';

    /**
     * Create a new instance
     *
     * @param string $api_key Your MailChimp API key
     *
     * @throws \Exception
     */
    public function __construct( $api_key ) {
        parent::__construct( $api_key );
    }

    public function validateParams( array $params ) {
        return $params;
    }

    /**
     * Get the list ID from the shortcode.
     *
     * @return string
     */
    public function getListId(): string {
        return 'c69310a8ba';
    }
}
