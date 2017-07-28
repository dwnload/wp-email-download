<?php

namespace Dwnload\WpEmailDownload\Admin;

use TheFrosty\WP\Utils\WpHooksInterface;

/**
 * Class AbstractSettings
 *
 * @package Dwnload\WpEmailDownload\Admin
 */
abstract class AbstractSettings implements WpHooksInterface {

    /** @var string $delimiter */
    protected $delimiter = ';';

    /** @var string $prefix */
    protected $prefix;

    /** @var array $settings */
    protected $settings = [];

    /**
     * Add hooks.
     */
    public function addHooks() {
        $this->settings = $this->getSettings();
        add_action( 'admin_init', [ $this, 'saveSettings' ] );
    }

    /**
     * Add a new setting
     *
     * @param string $setting The name of the new option
     * @param string|array $value The value of the new option
     *
     * @return bool True if successful, false otherwise
     */
    public function addSetting( string $setting = '', $value ): bool {
        if ( $setting === '' ) {
            return false;
        }

        if ( ! isset( $this->settings[ $setting ] ) ) {
            return $this->updateSetting( $setting, $value );
        }

        return false;
    }

    /**
     * Updates or adds a setting
     *
     * @param string $setting The name of the option
     * @param string|array $value The new value of the option
     *
     * @return bool True if successful, false if not
     */
    public function updateSetting( string $setting = '', $value ): bool {
        if ( $setting === '' ) {
            return false;
        }

        if ( empty( $this->settings ) ) {
            $this->settings = $this->getSettings();
        }
        /**
         * @filter wp_email_download_update_setting
         * @param mixed $value
         * @param string $setting
         * @param array $this->settings
         */
        $value = apply_filters( 'wp_email_download_update_setting', $value, $setting, $this->settings );
        $this->settings[ $setting ] = $value;

        return $this->updateSettings( $this->settings );
    }

    /**
     * Deletes a setting
     *
     * @param string $setting The name of the option
     *
     * @return bool True if successful, false if not
     */
    public function deleteSetting( string $setting = '' ): bool {
        if ( $setting === '' ) {
            return false;
        }

        if ( empty( $this->settings ) ) {
            $this->settings = $this->getSettings();
        }
        unset( $this->settings[ $setting ] );

        return $this->updateSettings( $this->settings );
    }

    /**
     * Retrieves a setting value
     *
     * @param string $setting The name of the option
     * @param string $type The return format preferred, string or array. Default: string
     * @param mixed $default
     *
     * @return mixed The value of the setting
     */
    public function getSetting( string $setting = '', $type = 'string', $default = false ) {
        if ( $setting === '' || ! isset( $this->settings[ $setting ] ) ) {
            return $default;
        }

        $type = $this->getAllowedType( $type );

        if ( empty( $this->settings ) ) {
            $this->settings = $this->getSettings();
        }

        $value = $this->settings[ $setting ];

        if ( $type === 'array' && ! empty( $value ) ) {
            $value = (array) explode( $this->delimiter, $value );
        }

        return apply_filters( $this->prefix . '_get_setting', $value, $setting );
    }

    /**
     * Generates HTML field name for a particular setting
     *
     * @param string $setting The name of the setting
     * @param string $type The return format of the field, string or array. Default: string
     *
     * @return string The name of the field
     */
    public function getFieldName( string $setting, $type = 'string' ) {
        $type = $this->getAllowedType( $type );

        return "{$this->prefix}_setting[$setting][$type]";
    }

    /**
     * Prints nonce for admin form
     */
    public function theNonce() {
        wp_nonce_field( "save_{$this->prefix}_settings", "{$this->prefix}_save" );
    }

    /**
     * Saves settings
     */
    public function saveSettings() {
        if ( isset( $_REQUEST["{$this->prefix}_setting"] ) &&
             check_admin_referer( "save_{$this->prefix}_settings", "{$this->prefix}_save" )
        ) {
            $new_settings = $_REQUEST["{$this->prefix}_setting"];

            foreach ( $new_settings as $setting_name => $setting_value ) {
                foreach ( $setting_value as $type => $value ) {
                    if ( $type === 'array' ) {
                        if ( ! is_array( $value ) && ! empty( $value ) ) {
                            $value = (array) explode( $this->delimiter, $value );
                        }

                        $this->updateSetting( $setting_name, $value );
                    } else {
                        $this->updateSetting( $setting_name, $value );
                    }
                }
            }

            do_action( "{$this->prefix}_settings_saved" );
        }
    }

    /**
     * Gets settings object
     *
     * @return array
     */
    public function getSettings(): array {
        return get_option( "{$this->prefix}_settings", [] );
    }

    /**
     * Sets settings object
     *
     * @param array $value The new settings object
     *
     * @return bool True if successful, false otherwise
     */
    public function updateSettings( $value ): bool {
        return update_option( "{$this->prefix}_settings", $value );
    }

    /**
     * Get the correct type.
     *
     * @param string $type
     *
     * @return string
     */
    protected function getAllowedType( string $type ): string {
        $allowed_types = [
            'string',
            'array',
        ];

        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'string';
        }

        return strtolower( $type );
    }

    /**
     * @param string $prefix
     *
     * @return Settings
     */
    protected abstract function setPrefix( string $prefix ): Settings;
}
