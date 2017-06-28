<?php

namespace Dwnload\WpEmailDownload\Http\Services;

/**
 * Class RegisterPostRoute
 *
 * @package Dwnload\WpEmailDownload\Http\Services
 */
abstract class RegisterPostRoute extends RouteService {

    protected function registerRoute( string $namespace, string $route, $callback, array $args = [] ) {
        self::registerRestRoute( $namespace, $route, $callback, \WP_REST_Server::CREATABLE, $args );
    }
}
