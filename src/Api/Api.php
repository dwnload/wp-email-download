<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\RestApi\DownloadController;
use Dwnload\WpEmailDownload\RestApi\SubscriptionController;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Api\Hash;
use Throwable;
use WP_REST_Request;
use function hash_equals;
use function hash_hmac;
use function is_string;
use function strrpos;
use function strtotime;
use function substr;
use function wp_salt;

/**
 * Class Api
 * @package Dwnload\WpEmailDownload\Api
 */
final class Api
{

    use Hash {
        encrypt as private hashEncrypt;
        decrypt as private hashDecrypt;
    }

    public const string ENCRYPTION_DELIMITER = '||';
    public const string MAC_SEPARATOR = '.';
    public const int MAX_SUBMISSIONS = 5;

    /**
     * Encrypt a string and append an HMAC, so the token can't be modified without the site's salt.
     * The encryption alone (AES-256-CBC in wp-utilities <= 3.9.x) isn't authenticated: flipping bits in the IV or
     * ciphertext changes the decrypted value (e.g. the attachment ID) without the key.
     * @param mixed $data The value to encrypt.
     * @param string|null $encryption_key Optional key.
     * @return string
     */
    public function encrypt(
        #[SensitiveParameter] mixed $data,
        #[SensitiveParameter] ?string $encryption_key = null
    ): string {
        $ciphertext = $this->hashEncrypt($data, $encryption_key);

        return $ciphertext . self::MAC_SEPARATOR . $this->getMac($ciphertext);
    }

    /**
     * Verify the HMAC and decrypt a token created by `encrypt()`.
     * @param string $data The token.
     * @param string|null $encryption_key Optional key.
     * @return false|string False when the token is missing its HMAC, was modified, or can't be decrypted.
     */
    public function decrypt(string $data, #[SensitiveParameter] ?string $encryption_key = null): false|string
    {
        $position = strrpos($data, self::MAC_SEPARATOR);
        if ($position === false) {
            return false;
        }

        $ciphertext = substr($data, 0, $position);
        if (!hash_equals($this->getMac($ciphertext), substr($data, $position + 1))) {
            return false;
        }

        try {
            return $this->hashDecrypt($ciphertext, $encryption_key);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get the Computer ID.
     * @link https://stackoverflow.com/a/18187783/558561
     * @return string
     */
    public function getComputerId(): string
    {
        static $computer_id;

        if (!$computer_id) {
            $server = Request::createFromGlobals()->server;
            $computer_id = (string)$server->get('HTTP_USER_AGENT', '');
            $computer_id .= (string)$server->get('LOCAL_ADDR', '');
            $computer_id .= (string)$server->get('LOCAL_PORT', '');
            $computer_id .= (string)$server->get('REMOTE_ADDR', '');
        }

        return $computer_id;
    }

    /**
     * Get the attachment URI.
     * @param WP_REST_Request $request
     * @return string Returns URI to uploaded attachment or empty string on failure.
     */
    public function getDecryptFileIdAttachmentUrl(WP_REST_Request $request): string
    {
        $file_id = $this->getFileIdFromRequest($request);
        $uri = wp_get_attachment_url($file_id);

        return is_string($uri) ? $uri : '';
    }

    /**
     * Build the REST URL to download the attachment.
     * @param string $email The current users email address
     * @param string $sub_hash The current users subscription hash from MailChimp
     * @param string $file_url The attachment URL
     * @param int|string $file_id The attachment ID
     * @return string
     */
    public function buildDownloadRestUrl(string $email, string $sub_hash, string $file_url, int|string $file_id): string
    {
        $data = $this->encrypt(
            sprintf(
                '%1$s%4$s%2$s%4$s%3$s%4$s%5$d%4$s%6$d',
                $email,
                $sub_hash,
                $file_url,
                self::ENCRYPTION_DELIMITER,
                absint($file_id),
                strtotime('+2 days'),
            )
        );
        $path = EmailDownload::ROUTE_NAMESPACE . DownloadController::ROUTE_PREFIX . $data;

        return get_rest_url(null, $path);
    }

    /**
     * Get the file id from the Request.
     * @param WP_REST_Request $request
     * @return int
     */
    public function getFileIdFromRequest(WP_REST_Request $request): int
    {
        $data = $this->decrypt((string)$request->get_param(SubscriptionController::DOWNLOAD_KEY));
        if (!is_string($data)) {
            return 0;
        }

        $file_id = explode(self::ENCRYPTION_DELIMITER, $data)[0];

        return absint($file_id);
    }

    /**
     * @param string $data
     * @return string
     */
    public function buildDataForFieldId(string $data): string
    {
        return sprintf('%s%s%s', $data, self::ENCRYPTION_DELIMITER, $this->getComputerId());
    }

    /**
     * Get the transient key.
     * @return string
     */
    public function getTransientKey(): string
    {
        return sprintf('email_download_%s', md5($this->getComputerId()));
    }

    /**
     * Get the HMAC for a ciphertext.
     * @param string $ciphertext
     * @return string
     */
    private function getMac(string $ciphertext): string
    {
        return hash_hmac('sha256', $ciphertext, wp_salt('secure_auth') . 'email-download/token');
    }

    /**
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return (new EmailValidator())
            ->isValid(
                $email,
                new MultipleValidationWithAnd([
                    new RFCValidation(),
                    new DNSCheckValidation(),
                ])
            );
    }

    /**
     * Delete the transient data.
     * @return mixed
     */
    public function deleteTransientData(): mixed
    {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_dwnload/mailchimp_lists_') . '%',
                $wpdb->esc_like('_transient_timeout_dwnload/mailchimp_lists_') . '%'
            )
        );
    }
}
