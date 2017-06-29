<?php

namespace Dwnload\WpEmailDownload\ShortcodeApi\Handler;

/**
 * Interface ShortcodeHandler
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\Handler
 */
interface ShortcodeHandler {

    /**
     * @param string $tag
     */
    public function setTag( string $tag );

    /**
     * Returns the array of defaults for the shortcode's attributes
     *
     * @return array
     */
    public function getDefaults(): array;

    /**
     * Returns the html that WordPress will display to the user.
     * The type for $atts is not set since it changes. If there are no attributes, it's an empty string. Otherwise it's
     * an array.
     *
     * https://codex.wordpress.org/Shortcode_API#Output
     *
     * @param string|array $atts
     * @param string $content
     * @param string $tag
     *
     * @return string
     */
    public function handler( $atts, $content, $tag ): string;
}
