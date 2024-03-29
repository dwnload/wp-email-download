<?php

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\EmailDownload;
use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;
use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeUiTrait;
use function Dwnload\WpEmailDownload\admin_notice;
use function Dwnload\WpEmailDownload\missing_shorcode_ui_text;
use Dwnload\WpSettingsApi\Api\Options;

/**
 * Class EmailDownloadHandler
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class Handler implements ShortcodeHandler {

    use ShortcodeUiTrait;

    const ATTRIBUTE_LIST_ID = 'list-id';
    const ATTRIBUTE_FILE = 'file';
    const SCRIPT_HANDLE = 'email-download';

    /** @var array $atts */
    protected $atts = [];

    /** @var Api $api */
    protected $api;

    /** @var string $tag */
    protected $tag;

    /**
     * Handler constructor.
     *
     * @param Api $api
     */
    public function __construct( Api $api ) {
        $this->api = $api;
    }

    /**
     * Initiate the registration of the Shorcode UI on plugins_loaded
     * so we can catch the Exception if the plugin isn't installed or activated.
     */
    public function pluginsLoaded() {
        add_action( 'plugins_loaded', function() {
            try {
                $this->addActionRegisterShortcodeUi();
            } catch ( \Exception $e ) {
                add_action( 'admin_notices', function() {
                    admin_notice( missing_shorcode_ui_text(), 'warning' );
                } );
            }
        } );
        add_action( 'wp_enqueue_scripts', [ $this, 'registerScripts' ] );
    }

    /**
     * Register our shortcode output stylesheet.
     */
    public function registerScripts() {
        wp_register_style( self::SCRIPT_HANDLE, plugins_url( 'assets/css/style.css', EmailDownload::getFile() ) );
    }

    /**
     * @param string $tag
     */
    public function setTag( string $tag ) {
        $this->tag = $tag;
    }

    /**
     * Returns the defaults per the requirement for ShortcodeHandler interface.
     *
     * @return array
     */
    public function getDefaults(): array {
        return [
            self::ATTRIBUTE_LIST_ID => '',
            self::ATTRIBUTE_FILE => '',
        ];
    }

    /**
     * Get an attribute from the attributes array.
     *
     * @param string $attr
     *
     * @return string
     */
    public function getAttribute( string $attr ): string {
        return $this->atts[ $attr ] ?? '';
    }

    /**
     * Returns the html for the height spacer.
     *
     * @param array|string $atts
     * @param string $content
     * @param string $tag
     *
     * @return string
     */
    public function handler( $atts, $content, $tag ): string {
        $this->atts = $parsed_atts = shortcode_atts( $this->getDefaults(), $atts );

        $list_id = $parsed_atts[ self::ATTRIBUTE_LIST_ID ];
        if ( empty( $list_id ) ) {
            return 'Please provide a List ID.';
        }

        $file = $parsed_atts[ self::ATTRIBUTE_FILE ];
        if ( empty( $file ) ) {
            return 'Please provide a file.';
        }

        if ( wp_style_is( self::SCRIPT_HANDLE, 'registered' ) ) {
            wp_enqueue_style( self::SCRIPT_HANDLE );
        }
        if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
            wp_enqueue_script( self::SCRIPT_HANDLE );
        }

        ob_start();
        $api = $this->api;
        include __DIR__ . '/views/form.php';

	    return ob_get_clean();
    }

    public function registerShortcodeUI() {
        $fields = [
            [
                'label' => esc_html__( 'Mailchimp List ID', 'email-download' ),
                'description' => esc_html__( 'The list which a user needs to be subscribed to before gaining access to download.', 'email-download' ),
                'attr' => self::ATTRIBUTE_LIST_ID,
                'type' => 'select',
                'options' => $this->getMailchimpLists(),
            ],
            [
                'label' => esc_html__( 'File', 'email-download' ),
                'description' => esc_html__( 'The attachment.', 'email-download' ),
                'attr' => 'file',
                'type' => 'attachment',
            ],
        ];
        $shortcode_ui_args = [
            'label' => esc_html__( 'Email Download shortcode', 'email-download' ),
            'listItemImage' => 'dashicons-download',
            'post_type' => [ 'post', 'page' ],
            'attrs' => $fields,
        ];

        $this->shortcodeUiRegisterShortcode( $this->tag, $shortcode_ui_args );
    }

    /**
     * @return array
     */
    protected function getMailchimpLists(): array {
        $options = [ '0' => 'No Lists found.' ];

        if ( ! empty( $api_key = Options::getOption( Mailchimp::SETTING_API_KEY ) ) ) {
            try {
                return ( new MailChimp( $api_key ) )->getListsArray( true );
            } catch ( \Exception $e ) {
                return $options;
            }
        }

        return $options;
    }
}
