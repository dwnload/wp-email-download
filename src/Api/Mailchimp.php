<?php

namespace Dwnload\WpEmailDownload\Api;

/**
 * Class Mailchimp
 *
 * @package Dwnload\WpEmailDownload\Api
 */
class Mailchimp extends \DrewM\MailChimp\MailChimp {

    const LIST_ID = 'list_id';
    const SETTING_API_KEY = 'api_key';
    const SETTING_LIST_ID = self::LIST_ID;

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

    /**
     * Get the list ID from the shortcode.
     *
     * @return string
     */
    public function getLists(): string {
        $reflection = new \ReflectionClass( parent::class );
        $api_key = $reflection->getProperty( 'api_key' );
        $api_key->setAccessible( true );
        $transient = sprintf( 'dwnload/mailchimp_lists_%s', base64_encode( $api_key ) );
        $api_key->setAccessible( false );

        if ( ( $response = get_transient( $transient ) ) === false ) {
            $response = $this->get( 'lists' );
            if ( is_array( $response ) ) {
                set_transient( $transient, $response, WEEK_IN_SECONDS );
            }
        }

        $html = esc_html__( 'Error connecting to MailChimp.', 'email-download' );

        if ( isset( $response['lists'] ) ) {
            $html = '<ul style="margin: 0">';
            foreach ( $response['lists'] as $list ) {
                $html .= "<li>{$list['name']} ({$list['id']})</li>";
            }
            $html .= '</ul>';
        }

        return $html;
    }
}
