<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Http\Services;

/**
 * Class RegisterGetRoute
 * @package Dwnload\WpEmailDownload\Http\Services
 */
abstract class RegisterGetRoute extends RouteService
{

    protected function registerRoute(string $namespace, string $route, $callback, array $args = []): void
    {
        self::registerRestRoute($namespace, $route, $callback, \WP_REST_Server::READABLE, $args);
    }
}
