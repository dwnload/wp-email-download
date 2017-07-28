<?php

namespace Dwnload\WpEmailDownload;

use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\DownloadController;
use Dwnload\WpEmailDownload\Api\Scripts;
use Dwnload\WpEmailDownload\Api\SubscriptionController;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Handler;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Shortcode;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\ShortcodeRegistration;
use TheFrosty\WP\Utils\Init;

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
     *
     * @return EmailDownload
     */
    public function setInit( Init $init ): EmailDownload {
        $this->init = $init;

        return $this;
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
        $settings = ( new Settings() )->setPrefix( 'email_download' );
        if ( is_admin() ) {
            $this->getInit()
                ->add( $settings )
                ->initialize();
        }

        $api = new Api();
        $this->getInit()
            ->add( new Scripts() )
            ->add( new SubscriptionController( $api, $settings ) )
            ->add( new DownloadController( $api, $settings ) )
            ->add( new ShortcodeRegistration( new Shortcode( 'email_to_download', new Handler( $api, $settings ) ) ) )
            ->initialize();
    }

    /**
     * @param string $file
     */
    public static function setFile( string $file ) {
        self::$file = $file;
    }

    /**
     * @return string
     */
    public static function getFile(): string {
        return self::$file;
    }
}
