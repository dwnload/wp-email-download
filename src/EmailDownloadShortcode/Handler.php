<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\Api\ApiFactory;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\Api\Scripts;
use Dwnload\WpSettingsApi\Api\Options;
use Exception;
use TheFrosty\WpUtilities\Api\Shortcode\Handler\HandlerInterface;
use TheFrosty\WpUtilities\Api\Shortcode\Handler\ShortcodeUiTrait;
use WP_Error;
use function array_merge;
use function Dwnload\WpEmailDownload\admin_notice;
use function Dwnload\WpEmailDownload\missing_shorcode_ui_text;
use function esc_html__;

/**
 * Class EmailDownloadHandler
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class Handler implements HandlerInterface
{

    use ApiFactory;
    use ShortcodeUiTrait;

    const string ATTRIBUTE_LIST_ID = 'list-id';
    const string ATTRIBUTE_FILE = 'file';

    /** @var array $atts */
    protected array $atts = [];

    /** @var string $tag */
    protected string $tag;

    /**
     * Initiate the registration of the Shorcode UI on plugins_loaded
     * so we can catch the Exception if the plugin isn't installed or activated.
     */
    public function pluginsLoaded(): void
    {
        add_action('plugins_loaded', function (): void {
            try {
                $this->addActionRegisterShortcodeUi();
            } catch (Exception) {
                add_action('admin_notices', static function (): void {
                    admin_notice(missing_shorcode_ui_text(), 'warning');
                });
            }
        });
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * Returns the defaults per the requirement for HandlerInterface interface.
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            self::ATTRIBUTE_LIST_ID => '',
            self::ATTRIBUTE_FILE => '',
        ];
    }

    /**
     * Get an attribute from the attributes array.
     * @param string $attr
     * @return string
     */
    public function getAttribute(string $attr): string
    {
        return $this->atts[$attr] ?? '';
    }

    /**
     * Returns the html for the height spacer.
     * @param array|string $atts
     * @param string|null $content
     * @param string $tag
     * @return string
     */
    public function handler(array|string $atts, ?string $content, string $tag): string
    {
        $this->atts = $parsed_atts = shortcode_atts($this->getDefaults(), $atts);

        $list_id = $parsed_atts[self::ATTRIBUTE_LIST_ID];
        $errors = new WP_Error();
        if (empty($list_id)) {
            $errors->add('missing_list_id', 'Please provide a List ID.');
        }

        $file = $parsed_atts[self::ATTRIBUTE_FILE];
        if (empty($file)) {
            $errors->add('missing_file', 'Please provide a download file.');
        }

        if ($errors->has_errors()) {
            $html = '<div class="EmailDownload__notice error"><ul>';
            foreach ($errors->get_error_codes() as $code) {
                $html .= sprintf(
                    '<li data-error-core="%s">%s</li>',
                    esc_attr($code),
                    esc_html($errors->get_error_message($code))
                );
            }
            $html .= '</ul></div>';
            return $html;
        }

        wp_enqueue_style(Scripts::SCRIPT_HANDLE);
        wp_enqueue_script(Scripts::SCRIPT_HANDLE);

        ob_start();
        $api = $this->api;
        include __DIR__ . '/views/form.php';
        unset($api); // Cleanup.

        return ob_get_clean();
    }

    public function registerShortcodeUI(): void
    {
        $fields = [
            [
                'label' => esc_html__('Mailchimp List ID', 'email-download'),
                'description' => esc_html__(
                    'The list which a user needs to be subscribed to before gaining access to download.',
                    'email-download'
                ),
                'attr' => self::ATTRIBUTE_LIST_ID,
                'type' => 'select',
                'options' => $this->getMailchimpLists(),
            ],
            [
                'label' => esc_html__('File', 'email-download'),
                'description' => esc_html__('The attachment.', 'email-download'),
                'attr' => 'file',
                'type' => 'attachment',
            ],
        ];
        $shortcode_ui_args = [
            'label' => esc_html__('Email Download shortcode', 'email-download'),
            'listItemImage' => 'dashicons-download',
            'post_type' => ['post', 'page'],
            'attrs' => $fields,
        ];

        $this->shortcodeUiRegisterShortcode($this->tag, $shortcode_ui_args);
    }

    /**
     * Retrieve our Mailchimp lists.
     * @return array
     */
    protected function getMailchimpLists(): array
    {
        $options = ['0' => 'No Lists found.'];

        if (!empty($api_key = Options::getOption(Mailchimp::SETTING_API_KEY))) {
            try {
                return (new MailChimp($api_key))->getListsArray(true);
            } catch (Exception) {
                return $options;
            }
        }

        return $options;
    }
}
