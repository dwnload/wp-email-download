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
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Api\Hash;
use WP_REST_Request;
use function hash_equals;
use function is_string;
use function strtotime;

/**
 * Class Api
 * @package Dwnload\WpEmailDownload\Api
 */
final class Api
{

    use Hash;

    public const string ENCRYPTION_DELIMITER = '||';
    public const int MAX_SUBMISSIONS = 5;

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
        $data = $this->decrypt($request->get_param(SubscriptionController::DOWNLOAD_KEY));
        $file_id = explode(self::ENCRYPTION_DELIMITER, $data)[0];

        return absint($file_id);
    }

    /**
     * Build the file field data. The list ID is included so a file token only works with the list it was
     * rendered for: otherwise the `list_id` field of a form for a free list could be paired with the `file_id`
     * field of a form for another list.
     * @param string $data The attachment ID.
     * @param string $list_id The Mailchimp list ID the form is for.
     * @return string
     */
    public function buildDataForFieldId(string $data, string $list_id): string
    {
        return sprintf(
            '%1$s%2$s%3$s%2$s%4$s',
            $data,
            self::ENCRYPTION_DELIMITER,
            $list_id,
            $this->getComputerId()
        );
    }

    /**
     * Does the request's file token belong to the list ID?
     * @param WP_REST_Request $request
     * @param string $list_id The decrypted list ID from the request.
     * @return bool
     */
    public function isFileForList(WP_REST_Request $request, string $list_id): bool
    {
        $data = $this->decrypt((string)$request->get_param(SubscriptionController::DOWNLOAD_KEY));
        if (!is_string($data) || $list_id === '') {
            return false;
        }

        $parts = explode(self::ENCRYPTION_DELIMITER, $data);

        return isset($parts[1]) && hash_equals($parts[1], $list_id);
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
