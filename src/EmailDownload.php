<?php

namespace Dwnload\WpEmailDownload;


use Dwnload\WpEmailDownload\Api\DownloadController;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Handler;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Shortcode;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\ShortcodeRegistration;

/**
 * Class EmailDownload
 *
 * @package Dwnload\WpEmailDownload
 */
class EmailDownload {

    /**
     * @var Init $init
     */
    private $init;

    /**
     * @param Init $init
     */
    public function __construct( Init $init ) {
        $this->init = $init;
    }

    /**
     * @return Init
     */
    public function getInit(): Init {
        return $this->init;
    }

    /**
     * Initiate all class hookups.
     */
    public function hookup() {
        $this->getInit()
            ->add( new DownloadController() )
            ->add( new ShortcodeRegistration( new Shortcode( 'email_to_download', new Handler() ) ) )
            ->initialize();
    }

    public static function activationHook() {
    }
}
