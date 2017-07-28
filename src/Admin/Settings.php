<?php

namespace Dwnload\WpEmailDownload\Admin;

use Dwnload\WpEmailDownload\Api\Mailchimp;
use TheFrosty\WP\Utils\WpHooksInterface;

/**
 * Class Settings
 *
 * @package Dwnload\WpEmailDownload\Admin
 */
class Settings extends AbstractSettings implements WpHooksInterface {

    const SETTINGS_PAGE_SLUG = 'dwnload-email-download';

    /**
     * Add class hooks.
     */
    public function addHooks() {
        parent::addHooks();
        add_action( 'admin_menu', [ $this, 'addMenuPage' ] );
        add_filter( 'wp_email_download_update_setting', [ $this, 'sanitizeSetting' ], 10, 3 );
    }

    /**
     * Add the settings page.
     */
    public function addMenuPage() {
        add_options_page(
            'Email Download Settings',
            'Email Download',
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            [ $this, 'menuPageCallback', ]
        );
    }

    /**
     * Sanitize our settings, and be sure that any obfuscated settings don't get
     * saved that way.
     *
     * @param mixed $value The incoming setting key value.
     * @param string $setting The incoming setting key.
     * @param array $settings The full setting $_REQUEST array.
     *
     * @return mixed
     */
    public function sanitizeSetting( $value, string $setting, array $settings ) {
        switch ( $setting ) {
            case Mailchimp::SETTING_API_KEY:
                if ( $this->isObfuscated( $value ) ) {
                    $new_value = $settings[ Mailchimp::SETTING_API_KEY ];
                } else {
                    $new_value = sanitize_text_field( $value );
                }
                break;
            default:
                $new_value = sanitize_text_field( $value );
                break;
        }

        return $new_value;
    }

    /**
     * Add the settings page HTML
     */
    public function menuPageCallback() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        include 'views/settings.php';
    }

    /**
     * @param string $prefix
     *
     * @return Settings
     */
    public function setPrefix( string $prefix ): Settings {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @param string $setting
     * @param string $type
     * @param bool $default
     *
     * @return mixed|string
     */
    public function getObfuscatedSetting( string $setting = '', $type = 'string', $default = false ) {
        $value = parent::getSetting( $setting, $type, $default );

        if ( ! empty( $value ) ) {
            $len = 8;

            return str_repeat( '*', strlen( $value ) - $len ) . substr( $value, - $len, $len );
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isObfuscated( string $value ): bool {
        return strpos( $value, '*' ) !== false;
    }
}
