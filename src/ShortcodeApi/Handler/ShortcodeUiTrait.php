<?php

namespace Dwnload\WpEmailDownload\ShortcodeApi\Handler;

/**
 * Trait ShortcodeUiTrait
 *
 * @package Dwnload\WpEmailDownload\ShortcodeApi\Handler
 */
trait ShortcodeUiTrait {

    /**
     * Register shortcode ui method `registerShortcodeUI()` on the
     * custom 'register_shortcode_ui' action hook.
     *
     * @throws \Exception
     */
    protected function addActionRegisterShortcodeUi() {
        if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
            throw new \Exception( 'Shortcake plugin needs to be activated to use ' . __METHOD__ );
        }
        add_action( 'register_shortcode_ui', [ $this, 'registerShortcodeUI' ] );
    }

    /**
     * Required registerShortcodeUI method.
     */
    abstract public function registerShortcodeUI();

    /**
     * Helper to register the Shortcode UI for shortcode callback using Shotcode UI.
     * This method will have a fatal error unless Shortcake plugin is active. The
     * Dependencies class can be used to check for whether shortcake plugin is active.
     *
     * @param string $shortcode_slug
     * @param array $shortcode_ui_args
     */
    protected function shortcodeUiRegisterShortcode( string $shortcode_slug, array $shortcode_ui_args ) {
        if ( function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
            shortcode_ui_register_for_shortcode( $shortcode_slug, $shortcode_ui_args );
        }
    }
}
