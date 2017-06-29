<?php

namespace Dwnload\WpEmailDownload;

use Dwnload\WpEmailDownload\Admin\Settings;
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

    const ROUTE_NAMESPACE = 'dwnload/v1';

    /**
     * @var Init $init
     */
    private $init;

    /** @var string $file */
    private static $file;

    /**
     * @param Init $init
     * @param string $file
     */
    public function __construct( Init $init, string $file ) {
        $this->init = $init;
        if ( ! isset( self::$file ) ) {
            self::$file = $file;
        }
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
        $settings = new Settings();
        if ( is_admin() ) {
            $this->getInit()
                ->add( $settings )
                ->initialize();
        }

        $this->getInit()
            ->add( new DownloadController( $settings ) )
            ->add( new ShortcodeRegistration( new Shortcode( 'email_to_download', new Handler( $settings ) ) ) )
            ->initialize();
    }

    /**
     * @return string
     */
    public static function getFile(): string {
        return self::$file;
    }

    public static function activationHook() {
    }
}
