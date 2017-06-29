<?php

namespace Dwnload\WpEmailDownload\Admin;

use Dwnload\WpEmailDownload\WpHooksInterface;

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
}
