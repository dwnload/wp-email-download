<?php

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\WpHooksInterface;
use Dwnload\WpEmailDownload\ShortcodeApi\AbstractShortcode;
use Dwnload\WpEmailDownload\ShortcodeApi\ShortcodeInterface;

/**
 * Class ShortcodeRegistration
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class ShortcodeRegistration extends AbstractShortcode implements WpHooksInterface {

    /**
     * @var ShortcodeInterface $shortcode
     */
    protected $shortcode;

    /**
     * ShortcodeRegistration constructor.
     *
     * @param ShortcodeInterface $shortcode
     */
    public function __construct( ShortcodeInterface $shortcode ) {
        $this->shortcode = $shortcode;
    }

    public function addHooks() {
        add_action( 'init', [ $this, 'addShortcode' ] );
    }

    public function addShortcode() {
        add_shortcode( $this->shortcode->getTag(), [ $this->shortcode->getHandler(), 'handler' ] );
    }
}
