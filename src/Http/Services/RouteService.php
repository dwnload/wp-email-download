<?php

namespace Dwnload\WpEmailDownload\Http\Services;

use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class RouteService
 *
 * @package Dwnload\WpEmailDownload\Http\Services
 */
abstract class RouteService implements WpHooksInterface {

    const NONCE_ACTION = 'wp_rest';

    /**
     * Adds hooks
     */
    public function addHooks(): void {
        add_action( 'rest_api_init', [ $this, 'initializeRoute' ] );
    }

    /**
     * Registers a REST API route.
     *
     * @param string $namespace The first URL segment after core prefix. Should be unique to your package/plugin.
     * @param string $route The base URL for route you are adding.
     * @param string|array|callable $callback Callback method to run when endpoint is accessed
     * @param string $method The HTTP method to be processed by the callback function
     * @param array $args
     */
    protected static function registerRestRoute( string $namespace, string $route, $callback, string $method, array $args = [] ) {
        $defaults = [
            'methods' => $method,
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ];
        $args = wp_parse_args( $args, $defaults );

        register_rest_route( $namespace, $route, $args );
    }

    abstract public function initializeRoute();

    /**
     * @param string $namespace
     * @param string $route
     * @param string|array|callable $callback
     */
    abstract protected function registerRoute( string $namespace, string $route, $callback );
}
