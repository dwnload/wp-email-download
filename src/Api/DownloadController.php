<?php

namespace Dwnload\WpEmailDownload\Api;

use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\Http\Services\RegisterGetRoute;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class DownloadController
 *
 * @package Dwnload\WpEmailDownload\Api
 */
class DownloadController extends RegisterGetRoute {

    const ENCRYPTION_KEY = 'D0WnL0ADk3Y';
    const MIME_TYPE = 'application/octet-stream';
    const NONCE_NAME = 'time';
    const ROUTE_FILE_PREFIX = '/download/';
    const ROUTE_REQUIRED_FIELD = 'data';

    /** @var Api $api */
    protected $api;

    /**
     * DownloadController constructor.
     *
     * @param Api $api
     */
    public function __construct( Api $api ) {
        $this->api = $api;
    }

    /**
     * Registers a REST API route.
     * @todo add permission_callback to $args param of registerRoute.
     */
    public function initializeRoute() {
        $this->registerRoute(
            EmailDownload::ROUTE_NAMESPACE,
            self::ROUTE_FILE_PREFIX . "(?P<" . self::ROUTE_REQUIRED_FIELD . ">\S+)",
            [ $this, 'validateDownloadableFile' ],
            [
                'args' => [
                    self::ROUTE_REQUIRED_FIELD => [
                        'required' => true,
                        'validate_callback' => function( $value ): bool {
                            $data = $this->api->decrypt( $value, self::ENCRYPTION_KEY );
                            list( $email_address ) = explode( Api::ENCRYPTION_DELIMITER, $data );

                            return $this->api->isValidEmail( $email_address );
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return mixed|WP_REST_Response
     */
    public function validateDownloadableFile( WP_REST_Request $request ) {
        // Required parameters (though the 'data' field is required by the route
        if ( empty( $request->get_param( self::ROUTE_REQUIRED_FIELD ) ) ) {
            return rest_ensure_response( new \WP_Error(
                'missing_params',
                'One or more parameters are missing from this request.',
                [ 'status' => \WP_Http::OK ]
            ) );
        }

        $data = $request->get_param( self::ROUTE_REQUIRED_FIELD );
        $value = $this->api->decrypt( $data, self::ENCRYPTION_KEY );
        list( $email, , $file_url ) = explode( Api::ENCRYPTION_DELIMITER, $value );

        // Required parameters (though the 'data' field is required by the route
        if ( empty( $file_url ) ) {
            return rest_ensure_response( new \WP_Error(
                'missing_file_url',
                'The file URL couldn\'t be processed, please try again.',
                [ 'status' => \WP_Http::OK ]
            ) );
        }

        return new WP_REST_Response(
            file_get_contents( $file_url ),
            \WP_Http::OK,
            $this->getDownloadHeaders( $file_url )
        );
    }

    /**
     * @param string $file_path
     *
     * @return array
     */
    private function getDownloadHeaders( string $file_path ): array {
        global $is_IE;

        // Get file name
        $file_name = urldecode( basename( $file_path ) );
        $file_size = $this->getFileSize( $file_path );
        if ( strstr( $file_name, '?' ) ) {
            $file_name = current( explode( '?', $file_name ) );
        }
        $headers = [];
        if ( $is_IE && is_ssl() ) {
            // IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
            $headers['Expires'] = 'Wed, 11 Jan 1984 05:00:00 GMT';
            $headers['Cache-Control'] = 'private';
        } else {
            nocache_headers();
        }
        $headers['Pragma'] = 'public';
        $headers['X-Robots-Tag'] = 'noindex, nofollow';
        $headers['Content-Type'] = self::MIME_TYPE;
        $headers['Content-Description'] = 'File Transfer';
        $headers['Content-Disposition'] = "attachment; filename=\"{$file_name}\";";
        $headers['Content-Transfer-Encoding'] = 'binary';
        $headers['Connection'] = 'close';
        if ( $file_size !== -1 ) {
            $headers['Content-Length'] = $file_size;
            $headers['Accept-Ranges'] = 'bytes';
        }
        $headers = apply_filters( 'email_download_force_download_headers', $headers, $file_path );

        return $headers;
    }

    /**
     * Gets the filesize of a path or URL.
     *
     * @access public
     *
     * @param string $file_path
     *
     * @return string size on success, -1 on failure
     */
    private function getFileSize( string $file_path ) {
        list( $file_path, $remote_file ) = $this->parseFilePath( $file_path );
        if ( ! empty( $file_path ) ) {
            if ( $remote_file ) {
                $file = wp_remote_head( $file_path );
                if ( ! is_wp_error( $file ) && ! empty( $file['headers']['content-length'] ) ) {
                    return $file['headers']['content-length'];
                }
            } else {
                if ( file_exists( $file_path ) && ( $file_size = filesize( $file_path ) ) ) {
                    return $file_size;
                }
            }
        }

        return ( -1 );
    }

    /**
     * Parse a file path and return the new path and whether or not it's remote
     *
     * @param string $file_path
     *
     * @return array
     */
    private function parseFilePath( $file_path ): array {
        $remote_file = true;
        $parsed_file_path = parse_url( $file_path );
        $wp_uploads = wp_upload_dir();
        $wp_uploads_dir = $wp_uploads['basedir'];
        $wp_uploads_url = $wp_uploads['baseurl'];
        if ( ( ! isset( $parsed_file_path['scheme'] ) ||
                ! in_array( $parsed_file_path['scheme'], [ 'http', 'https', 'ftp', ] ) ) &&
            isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] )
        ) {
            /** This is an absolute path */
            $remote_file = false;
        } elseif ( strpos( $file_path, $wp_uploads_url ) !== false ) {
            /** This is a local file given by URL so we need to figure out the path */
            $remote_file = false;
            $file_path = trim( str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path ) );
            $file_path = realpath( $file_path );
        } elseif ( is_multisite() && (
                strpos( $file_path, network_site_url( '/', 'http' ) ) !== false ||
                strpos( $file_path, network_site_url( '/', 'https' ) ) !== false
            )
        ) {
            /** This is a local file outside of wp-content so figure out the path */
            $remote_file = false;
            // Try to replace network url
            $file_path = str_replace( network_site_url( '/', 'https' ), ABSPATH, $file_path );
            $file_path = str_replace( network_site_url( '/', 'http' ), ABSPATH, $file_path );
            // Try to replace upload URL
            $file_path = str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path );
            $file_path = realpath( $file_path );
        } elseif ( strpos( $file_path, site_url( '/', 'http' ) ) !== false ||
            strpos( $file_path, site_url( '/', 'https' ) ) !== false
        ) {
            /** This is a local file outside of wp-content so figure out the path */
            $remote_file = false;
            $file_path = str_replace( site_url( '/', 'https' ), ABSPATH, $file_path );
            $file_path = str_replace( site_url( '/', 'http' ), ABSPATH, $file_path );
            $file_path = realpath( $file_path );
        } elseif ( file_exists( ABSPATH . $file_path ) ) {
            /** Path needs an abspath to work */
            $remote_file = false;
            $file_path = ABSPATH . $file_path;
            $file_path = realpath( $file_path );
        }

        return [ $file_path, $remote_file ];
    }
}
