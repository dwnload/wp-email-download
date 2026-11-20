<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\RestApi;

use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\ApiFactory;
use Dwnload\WpEmailDownload\EmailDownload;
use TheFrosty\WpUtilities\RestApi\Http\RegisterGetRoute;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function absint;
use function apply_filters;
use function basename;
use function esc_attr;
use function explode;
use function file_get_contents;
use function filesize;
use function get_attached_file;
use function get_post_mime_type;
use function get_post_type;
use function is_file;
use function is_numeric;
use function is_readable;
use function is_string;
use function is_ssl;
use function json_encode;
use function nocache_headers;
use function rest_ensure_response;
use function sanitize_file_name;
use function time;

/**
 * Class DownloadController
 * @package Dwnload\WpEmailDownload\Api
 */
class DownloadController extends RegisterGetRoute
{

    use ApiFactory;

    const string MIME_TYPE = 'application/octet-stream';
    const string ROUTE_PREFIX = '/download/';
    const string ROUTE_REQUIRED_FIELD = 'data';

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
            [$this, 'validateDownloadableFile'],
            [
                'args' => [
                    self::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function ($value): bool {
                            $data = $this->api->decrypt($value);
                            [$email_address] = explode(Api::ENCRYPTION_DELIMITER, $data);

                            return $this->api->isValidEmail($email_address);
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function validateDownloadableFile(WP_REST_Request $request): WP_Error|WP_REST_Response
    {
        // Required parameters (though the 'data' field is required by the route
        if (empty($request->get_param(self::ROUTE_REQUIRED_FIELD))) {
            return rest_ensure_response(
                new WP_Error(
                    'missing_params',
                    'One or more parameters are missing from this request.',
                    ['status' => WP_Http::OK]
                )
            );
        }

        $data = $request->get_param(self::ROUTE_REQUIRED_FIELD);
        $value = $this->api->decrypt($data);
        /*
         * @see SubscriptionController:138
         * $email_address, $subscriber, $file_url, $file_id, $expires (time)
         * The `$file_url` is ignored: the file is always resolved from the attachment ID, so a token can never point
         * the download at an arbitrary path or URL (e.g. `wp-config.php` or an internal host).
         */
        [, , , $file_id, $expires] = explode(Api::ENCRYPTION_DELIMITER, $value);

        // Check on the expiration time.
        if (!is_numeric($expires) || (int)$expires < time()) {
            return rest_ensure_response(
                new WP_Error(
                    'download_expired',
                    "The download has expired.",
                    ['status' => WP_Http::OK]
                )
            );
        }

        // Only serve files attached to the Media Library.
        $file_id = absint($file_id);
        $file_path = get_post_type($file_id) === 'attachment' ? get_attached_file($file_id) : false;
        if (!is_string($file_path) || !is_file($file_path) || !is_readable($file_path)) {
            return rest_ensure_response(
                new WP_Error(
                    'missing_file_url',
                    "The file couldn't be processed, please try again.",
                    ['status' => WP_Http::OK]
                )
            );
        }

        return new WP_REST_Response(
            file_get_contents($file_path),
            WP_Http::OK,
            $this->getDownloadHeaders($file_path, $file_id)
        );
    }

    /**
     * Set the headers.
     * @param string $file_path
     * @param int|string $file_id
     * @return array
     */
    private function getDownloadHeaders(string $file_path, int|string $file_id): array
    {
        global $is_IE;

        // Get file name.
        // Strip quotes and other characters that could break out of the Content-Disposition header value.
        $file_name = sanitize_file_name(basename($file_path));
        $file_size = $this->getFileSize($file_path);
        $headers = [];
        if ($is_IE && is_ssl()) {
            // IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
            $headers['Expires'] = 'Wed, 11 Jan 1984 05:00:00 GMT';
            $headers['Cache-Control'] = 'private';
        } else {
            nocache_headers();
        }
        $mime = get_post_mime_type($file_id);
        $headers['Pragma'] = 'public';
        $headers['X-Robots-Tag'] = 'noindex, nofollow';
        $headers['Content-Type'] = esc_attr($mime !== false ? $mime : self::MIME_TYPE);
        $headers['Content-Description'] = 'File Transfer';
        $headers['Content-Disposition'] = "attachment; filename=\"$file_name\";";
        $headers['Content-Transfer-Encoding'] = 'binary';
        $headers['Connection'] = 'close';
        if ($file_size !== -1) {
            $headers['Content-Length'] = $file_size;
            $headers['Accept-Ranges'] = 'bytes';
        }

        return apply_filters('email_download_force_download_headers', $headers, $file_name, $file_path);
    }

    /**
     * Gets the filesize of a local file.
     * @param string $file_path
     * @return int size on success, -1 on failure
     */
    private function getFileSize(string $file_path): int
    {
        $file_size = filesize($file_path);

        return $file_size !== false ? $file_size : -1;
    }
}
