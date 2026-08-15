<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Admin;

use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpSettingsApi\Api\Options;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\WpSettingsApi;
use Exception;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use function __;
use function add_action;
use function esc_html__;

/**
 * Class Settings
 * @package Dwnload\WpEmailDownload\Admin
 */
class Settings extends AbstractHookProvider
{

    protected const string SETTING_ID_S = 'email_download_%s';
    protected const string MAILCHIMP_SETTING = 'mailchimp';

    /**
     * Register our callback to the BB WP Settings API action hook
     * `App::ACTION_PREFIX . 'init'`. This custom action passes two parameters
     * so you have to register a priority and the parameter count.
     */
    public function addHooks(): void
    {
        add_action(WpSettingsApi::ACTION_PREFIX . 'init', [$this, 'init'], 10, 3);
        add_action(WpSettingsApi::ACTION_PREFIX . 'after_sanitize_options', static function (array $options): void {
            $api_key = Options::getOption(Mailchimp::SETTING_API_KEY);
            if ($api_key !== $options[Mailchimp::SETTING_API_KEY]) {
                global $wpdb;
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                        '%' . $wpdb->esc_like('dwnload/mailchimp_lists_') . '%',
                    )
                );
            }
        });
    }

    /**
     * Initiate our setting to the Section & Field Manager classes.
     * @param SectionManager $section_manager
     * @param FieldManager $field_manager
     * @param WpSettingsApi $wp_settings_api
     */
    public function init(
        SectionManager $section_manager,
        FieldManager $field_manager,
        WpSettingsApi $wp_settings_api
    ): void {
        if (!$wp_settings_api->isCurrentMenuSlug($this->getPlugin()->getSlug())) {
            return;
        }
        /**
         * License Settings Section
         */
        $section_id = $section_manager->addSection(
            new SettingSection([
                SettingSection::SECTION_ID => sprintf(self::SETTING_ID_S, self::MAILCHIMP_SETTING),
                SettingSection::SECTION_TITLE => 'MailChimp Settings',
            ])
        );

        $field = new SettingField([]);
        $field->setName(Mailchimp::SETTING_API_KEY);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $field->setDescription(
            sprintf(
                __(
                    'Enter your MailChimp API Key here. A valid API Key should have a data center at the end like: "%s" for example.',
                    'email-download'
                ),
                '-us6'
            )
        );
        // phpcs:enable
        $field->setLabel(esc_html__('MailChimp API Key', 'email-download'));
        $field->setType('text');
        $field->setSectionId($section_id);
        $field->setObfuscate();

        // Add the field.
        $field_manager->addField($field);

        // Lists array.
        $api_key = Options::getOption(Mailchimp::SETTING_API_KEY, $section_id);
        if (!empty($api_key)) {
            try {
                $options = (new MailChimp($api_key))->getListsArray();
                $options = array_merge(['0' => esc_html__('Select a list', 'email-download')], $options);
            } catch (Exception $e) {
                $field = new SettingField([]);
                $field->setName(Mailchimp::SETTING_LIST_ID);
                $field->setLabel(esc_html__('Error', 'email-download'));
                $field->setDescription($e->getMessage());
                $field->setType('html');
                $field->setSectionId($section_id);
                $field_manager->addField($field);
                // We have an error, don't continue.
                return;
            }

            $field = new SettingField([]);
            $field->setName(Mailchimp::SETTING_LIST_ID);
            $field->setLabel(esc_html__('Your Lists', 'email-download'));
            $field->setType('select');
            $field->setOptions($options);
            $field->setSectionId($section_id);

            // Add the field.
            $field_manager->addField($field);
        }
    }
}
