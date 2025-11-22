<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\ShortcodeApi;

/**
 * Class AbstractShortcode
 * @package Dwnload\WpEmailDownload\ShortcodeApi
 */
abstract class AbstractShortcode
{

    /**
     * Registers the shortcode with WordPress
     */
    abstract public function addShortcode(): void;
}
