<?php

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;
use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeUiTrait;
use function Dwnload\WpEmailDownload\admin_notice;
use function Dwnload\WpEmailDownload\missing_shorcode_ui_text;

/**
 * Class EmailDownloadHandler
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class Handler implements ShortcodeHandler {

    use ShortcodeUiTrait;

    const HEIGHT_SPACER_CSS_CLASS_PREFIX = 'bb-blog-height-spacer--lines-';
    const DEFAULT_LINE_HEIGHT = "3";
    const MAX_LINE_HEIGHT = "20";
    const ATTRIBUTE_NAME = "lines";

    /** @var Settings $settings */
    protected $settings;

    /** @var string $tag */
    protected $tag;

    /**
     * Handler constructor.
     *
     * @param Settings $settings
     */
    public function __construct( Settings $settings ) {
        try {
            $this->addActionRegisterShortcodeUi();
            $this->settings = $settings;
        } catch ( \Exception $e ) {
            add_action( 'admin_notices', function() {
                admin_notice( missing_shorcode_ui_text(), 'warning' );
            } );
        }
    }

    public function setTag( string $tag ) {
        $this->tag = $tag;
    }

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
     * The requirement was to not use inline style. The css for the class is defined in the scss
     * directory. Since the html is a one liner, it didn't make sense to put it into a template. If
     * it gets more complex, a template should be created. The class names only support line
     * heights from 1 - 20.
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

    public function registerShortcodeUI() {
        $fields = [
            [
                'label' => esc_html__( 'Mailchimp List ID', 'email-download' ),
                'description' => esc_html__( 'Message for when a user has already purchased a specific workshop.', 'email-download' ),
                'attr' => 'list-id',
                'type' => 'select',
                'options' => $this->getMailchimpLists(),
            ],
        ];
        $shortcode_ui_args = [
            'label' => esc_html__( 'Email Download shortcode', 'email-download' ),
            'listItemImage' => 'dashicons-download',
            'post_type' => [ 'page' ],
            'attrs' => $fields,
        ];

        $this->shortcodeUiRegisterShortcode( $this->tag, $shortcode_ui_args );
    }

    /**
     * @return array
     */
    protected function getMailchimpLists(): array {
        $options = [ '0' => 'No Lists found.' ];

        if ( ! empty( $api_key = $this->settings->getSetting( Mailchimp::SETTING_API_KEY ) ) ) {
            try {
                return ( new MailChimp( $api_key ) )->getListsArray();
            } catch ( \Exception $e ) {
                return $options;
            }
        }

        return $options;
    }

    /**
     * Validates the value of the "lines" attribute.
     *
     * We expect the "lines" attribute to be a string that is an integer between 1 and the maximum
     * number of lines we support in the css.
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
     * We need to wrap the class constant for the css class prefix for the height spacer since PHP
     * does not allow the use of constants in variable parsing in strings.
     *
     * @return string
     */
    protected function getHeightSpacerCSSClassPrefix() {
        return self::HEIGHT_SPACER_CSS_CLASS_PREFIX;
    }
}
