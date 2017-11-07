<?php

namespace Dwnload\WpEmailDownload\Admin;

use Dwnload\EddSoftwareLicenseManager\Edd\LicenseManager;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpSettingsApi\Api\Options;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\App;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use TheFrosty\WP\Utils\WpHooksInterface;

/**
 * Class Settings
 *
 * @package Dwnload\WpEmailDownload\Admin
 */
class Settings implements WpHooksInterface {

    const SETTING_ID_S = 'email_download_%s';
    const LICENSE_SETTING = 'license';
    const MAILCHIMP_SETTING = 'mailchimp';

    /** @var LicenseManager $license_manager */
    protected $license_manager;

    /**
     * WpSettingsApi constructor.
     *
     * @param LicenseManager $license_manager
     */
    public function __construct( LicenseManager $license_manager ) {
        $this->license_manager = $license_manager;
    }

    /**
     * @return LicenseManager $license_manager
     */
    public function getLicenseManager(): LicenseManager {
        return $this->license_manager;
    }

    /**
     * Register our callback to the BB WP Settings API action hook
     * `App::ACTION_PREFIX . 'init'`. This custom action passes two parameters
     * so you have to register a priority and the parameter count.
     */
    public function addHooks() {
        add_action( App::ACTION_PREFIX . 'init', [ $this, 'init' ], 10, 2 );
    }

    /**
     * Initiate our setting to the Section & Field Manager classes.
     *
     * SettingField requires the following settings (passes as an array or set explicitly):
     * [
     *  SettingField::NAME
     *  SettingField::LABEL
     *  SettingField::DESC
     *  SettingField::TYPE
     *  SettingField::SECTION_ID
     * ]
     *
     * @see SettingField for additional options for each field passed to the output
     *
     * @param SectionManager $section_manager
     * @param FieldManager $field_manager
     */
    public function init( SectionManager $section_manager, FieldManager $field_manager ) {
        /**
         * License Settings Section
         */
        $section_id = $section_manager->addSection(
            new SettingSection( [
                SettingSection::SECTION_ID => sprintf( self::SETTING_ID_S, self::MAILCHIMP_SETTING ),
                SettingSection::SECTION_TITLE => 'MailChimp Settings',
            ] )
        );

        $field = new SettingField();
        $field->setName( Mailchimp::SETTING_API_KEY );
        $field->setDescription(
            sprintf(
                __( 'Enter your MailChimp API Key here. A valid API Key should have a data center at the end like: "%s" for example.', 'email-download' ),
                '-us6'
            )
        );
        $field->setLabel( esc_html__( 'MailChimp API Key', 'email-download' ) );
        $field->setType( 'text' );
        $field->setSectionId( $section_id );
        $field->setObfuscate();

        // Add the field
        $field_manager->addField( $field );

        // Lists array
        if ( ! empty( $api_key = Options::getOption( Mailchimp::SETTING_API_KEY, $section_id ) ) ) {
            try {
                $options = ( new MailChimp( $api_key ) )->getListsArray();
            } catch ( \Exception $e ) {
                $description = $e->getMessage();
            }
            $field = new SettingField();
            $field->setName( Mailchimp::SETTING_LIST_ID );
            $field->setLabel( esc_html__( 'Your Lists', 'email-download' ) );
            $field->setDescription( $description ?? '' );
            $field->setType( 'select' );
            $field->setOptions( $options ?? [] );
            $field->setSectionId( $section_id );

            // Add the field
            $field_manager->addField( $field );
        }

        /**
         * License Settings Section
         */
        $section_id = $section_manager->addSection(
            new SettingSection( [
                SettingSection::SECTION_ID => sprintf( self::SETTING_ID_S, self::LICENSE_SETTING ),
                SettingSection::SECTION_TITLE => 'License Manager',
            ] )
        );

        $field = new SettingField();
        $field->setName( self::LICENSE_SETTING );
        $field->setDescription( esc_html__( 'Enter your licence key to enable updates.', 'email-download' ) );
        $field->setLabel( esc_html__( 'License Key', 'email-download' ) );
        $field->setType( 'text' );
        $field->setSectionId( $section_id );
        $field->setObfuscate();
        $this->getLicenseManager()->setSettingField( $field );

        $field_manager->addField( $field );
    }
}
