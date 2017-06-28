<?php

namespace Dwnload\WpEmailDownload\ShortcodeApi;

/**
 * Class AbstractShortcode
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi
 */
abstract class AbstractShortcode {

    /**
     * Registers the shortcode with WordPress
     */
    abstract public function addShortcode();
}
