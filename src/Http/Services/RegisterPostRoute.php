<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Http\Services;

/**
 * Class RegisterPostRoute
 * @package Dwnload\WpEmailDownload\Http\Services
 */
abstract class RegisterPostRoute extends RouteService
{

    protected function registerRoute(string $namespace, string $route, $callback, array $args = []): void
    {
        self::registerRestRoute($namespace, $route, $callback, \WP_REST_Server::CREATABLE, $args);
    }
}
