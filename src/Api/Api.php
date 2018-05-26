<?php

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\EmailDownload;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use WP_REST_Request;

final class Api {

    const ENCRYPTION_DELIMITER = '|';
    const ENCRYPTION_KEY = 'EMa1LD0WnL08D' . self::ENCRYPTION_DELIMITER;
    const MAX_SUBMISSIONS = 5;
    const SESSION_KEY = 'email_download';

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
        $iv = substr( hash( 'sha256', sprintf( '%s_iv', $encryption_key ) ), 0, 16 );

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
    public function encrypt( string $data, string $encryption_key = self::ENCRYPTION_KEY ) {
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $encryption_key );
        $iv = substr( hash( 'sha256', sprintf( '%s_iv', $encryption_key ) ), 0, 16 );

        return base64_encode( openssl_encrypt( $data, $encrypt_method, $key, 0, $iv ) );
    }

    /**
     * @link https://stackoverflow.com/a/18187783/558561
     * @return string
     */
    public function getComputerId(): string {
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
    public function getDecryptFileIdAttachmentUrl( WP_REST_Request $request ): string {
        $file_id = $this->getFileIdFromRequest( $request );
        $uri = wp_get_attachment_url( $file_id );

        return is_string( $uri ) ? $uri : '';
    }

    /**
     * Build the REST URL to download the attachment.
     *
     * @param string $email The current users email address
     * @param string $sub_hash The current users subscription hash from MailChimp
     * @param string $file_url The attachment URL
     *
     * @return string
     */
    public function buildDownloadRestUrl( string $email, string $sub_hash, string $file_url ): string {
        $data = $this->encrypt(
            sprintf(
                '%1$s%4$s%2$s%4$s%3$s',
                $email,
                $sub_hash,
                $file_url,
                self::ENCRYPTION_DELIMITER
            ),
            DownloadController::ENCRYPTION_KEY
        );
        $path = EmailDownload::ROUTE_NAMESPACE . DownloadController::ROUTE_FILE_PREFIX . $data;

        return get_rest_url( null, $path );
    }

    /**
     * Get the file id from the Request.
     *
     * @param WP_REST_Request $request
     *
     * @return int
     */
    public function getFileIdFromRequest( WP_REST_Request $request ): int {
        $data = $request->get_param( SubscriptionController::DOWNLOAD_KEY );
        $file_id = $this->decrypt( $data, self::getComputerId() );

        return absint( $file_id );
    }

    /**
     * Get the transient key.
     *
     * @return string
     */
    public function getTransientKey(): string {
        return sprintf( 'email_download_%s', md5( self::getComputerId() ) );
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isValidEmail( string $email ): bool {
        return ( new EmailValidator() )
            ->isValid( $email, new MultipleValidationWithAnd( [
                new RFCValidation(),
                new DNSCheckValidation(),
            ] ) );
    }

    /**
     * Delete the transient data.
     *
     * @return false|int
     */
    public function deleteTransientData() {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_dwnload/mailchimp_lists_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_dwnload/mailchimp_lists_' ) . '%'
            )
        );
    }
}