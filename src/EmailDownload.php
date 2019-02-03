<?php

namespace Dwnload\WpEmailDownload;

use Dwnload\EddSoftwareLicenseManager\Edd\LicenseManager;
use Dwnload\EddSoftwareLicenseManager\Edd\PluginUpdater;
use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\DownloadController;
use Dwnload\WpEmailDownload\Api\Scripts;
use Dwnload\WpEmailDownload\Api\SubscriptionController;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Handler;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Shortcode;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\ShortcodeRegistration;
use Dwnload\WpSettingsApi\Api\Options;
use Dwnload\WpSettingsApi\AppFactory;
use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpUtilities\Plugin\PluginFactory;

/**
 * Class EmailDownload
 *
 * @package Dwnload\WpEmailDownload
 */
class EmailDownload {

    const API_URL = 'https://plugingarden.dwnload.io/';
    const PLUGIN_NAME = 'Email Download';
    const PLUGIN_ITEM_ID = 11;
    const ROUTE_NAMESPACE = 'dwnload/v1';
    const SETTING_API_KEY = 'dwnload_api_key';

    /** @var string $file */
    private static $file;

    /**
     * Initiate all class hookups.
     */
    public function hookup() {
        $app = AppFactory::createApp( [
            'domain' => 'email-download',
            'file' => dirname( __DIR__ ) . '/vendor/dwnload/wp-settings-api/src', // Path to WPSettingsApi file.
            'menu-slug' => 'dwnload-email-download',
            'menu-title' => 'Email Download', // Title found in menu
            'page-title' => 'Email Download Settings', // Title output at top of settings page
            'prefix' => 'dwnload_email_download',
            'version' => self::getPluginData()['Version'],
        ] );

        $license_args = [
            'api_url' => self::API_URL,
            'plugin_file' => self::getFile(),
            'api_data' => [
                'version' => $app->getVersion(), // current version number
                'license' => Options::getOption(
                    Settings::LICENSE_SETTING,
                    sprintf( Settings::SETTING_ID_S, Settings::LICENSE_SETTING )
                ), // license key (used get_option above to retrieve from DB)
                'item_name' => self::PLUGIN_NAME, // name of this plugin (matching your EDD Download title)
                'item_id' => self::PLUGIN_ITEM_ID,
                'author' => 'Austin Passy', // author of this plugin
                'beta' => isset( $use_beta ),
            ],
            'item_id' => self::PLUGIN_ITEM_ID,
            'name' => plugin_basename( self::getFile() ),
            'slug' => basename( self::getFile(), '.php' ),
            'version' => $app->getVersion(),
            'wp_override' => false,
            'beta' => isset( $use_beta ),
        ];

        $plugin = PluginFactory::create('dwnload-email-download', self::getFile());
        $api = new Api();
        $license_manager = new LicenseManager( $app, $license_args );
        $plugin
            ->add( new WpSettingsApi( $app ) )
            ->add( new Settings( $license_manager ) )
            ->add( new Scripts() )
            ->add( new SubscriptionController( $api ) )
            ->add( new DownloadController( $api ) )
            ->add( new ShortcodeRegistration( new Shortcode( 'email_to_download', new Handler( $api ) ) ) )
            ->initialize();

        if ( is_admin() ) {
            $plugin
                ->add( new PluginUpdater( $license_args ) )
                ->add( $license_manager )
                ->initialize();
        }
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

    /**
     * @return array
     */
    public static function getPluginData(): array {
        static $plugin_data;

        if ( is_array( $plugin_data ) ) {
            return $plugin_data;
        }

        $default_headers = [
            'Name' => 'Plugin Name',
            'PluginURI' => 'Plugin URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
        ];

        $plugin_data = \get_file_data( self::$file, $default_headers, 'plugin' );

        return $plugin_data;
    }

    /**
     * @return array
     */
    private function getUpdaterArgs(): array {
        $data = self::getPluginData();
        $license = '';

        return [
            'api_url' => self::API_URL,
            'plugin_file' => self::$file,
            'api_data' => [
                'version' => $data['Version'], // current version number
                'license' => $license, // license key (used get_option above to retrieve from DB)
                'item_name' => $data['Name'], // name of this plugin (matching your EDD Download title)
                'author' => 'Austin Passy', // author of this plugin
                'beta' => false,
            ],
            'name' => plugin_basename( self::$file ),
            'slug' => basename( self::$file, '.php' ),
            'version' => $data['Version'],
            'wp_override' => false,
            'beta' => false,
        ];
    }
}
