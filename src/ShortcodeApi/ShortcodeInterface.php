<?php

namespace Dwnload\WpEmailDownload\ShortcodeApi;

use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;

/**
 * Interface ShortcodeInterface
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi
 */
interface ShortcodeInterface {

    /**
     * Returns the tag aka name for the shortcode
     *
     * @return string
     */
    public function getTag(): string;

    /**
     * Returns the ShortcodeHandler object that displays the html for a shortcode
     *
     * @return ShortcodeHandler
     */
    public function getHandler(): ShortcodeHandler;
}
