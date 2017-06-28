<?php

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;

/**
 * Class EmailDownloadHandler
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class Handler implements ShortcodeHandler {

    const HEIGHT_SPACER_CSS_CLASS_PREFIX = 'bb-blog-height-spacer--lines-';
    const DEFAULT_LINE_HEIGHT = "3";
    const MAX_LINE_HEIGHT = "20";
    const ATTRIBUTE_NAME = "lines";

    /**
     * Returns the defaults per the requirement for ShortcodeHandler interface.
     *
     * @return array
     */
    public function getDefaults(): array {
        return [ self::ATTRIBUTE_NAME => self::DEFAULT_LINE_HEIGHT ];
    }

    /**
     * Returns the html for the height spacer.
     *
     * The requirement was to not use inline style. The css for the class is defined in the scss directory. Since the
     * html is a one liner, it didn't make sense to put it into a template. If it gets more complex, a template should
     * be created. The class names only support line heights from 1 - 20.
     *
     * @param array|string $atts
     * @param string $content
     * @param string $tag
     *
     * @return string
     */
    public function handler( $atts, $content = '', $tag ): string {
        $parsed_atts = shortcode_atts( $this->getDefaults(), $atts );

        $line_height = $parsed_atts[ self::ATTRIBUTE_NAME ];
        $error_message = $this->validateLinesAttribute( $line_height ) ? "" :
            sprintf(
                __( 'The "%s" attribute for Beachbody Blog height spacer shortcode must be an integer between 1-%d. The 
                default value is %d', 'bb-blog' ),
                self::ATTRIBUTE_NAME,
                self::MAX_LINE_HEIGHT,
                self::DEFAULT_LINE_HEIGHT );

        return "<div class='{$this->getHeightSpacerCSSClassPrefix()}{$line_height}'>{$error_message}</div>";
    }

    /**
     * Validates the value of the "lines" attribute.
     *
     * We expect the "lines" attribute to be a string that is an integer between 1 and the maximum number of lines we
     * support in the css.
     *
     * @param string $lines_attribute
     *
     * @return bool
     */
    protected function validateLinesAttribute( $lines_attribute ) {
        return $lines_attribute === filter_var( $lines_attribute, FILTER_SANITIZE_NUMBER_INT ) &&
            (int)$lines_attribute >= 1 && (int)$lines_attribute <= self::MAX_LINE_HEIGHT;
    }

    /**
     * We need to wrap the class constant for the css class prefix for the height spacer since PHP does not allow the
     * use of constants in variable parsing in strings.
     *
     * @return string
     */
    protected function getHeightSpacerCSSClassPrefix() {
        return self::HEIGHT_SPACER_CSS_CLASS_PREFIX;
    }
}
