<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Api;

use Exception;
use ReflectionClass;

/**
 * Class Mailchimp
 * @package Dwnload\WpEmailDownload\Api
 */
class Mailchimp extends \DrewM\MailChimp\MailChimp
{

    const string LIST_ID = 'list_id';
    const string SETTING_API_KEY = 'api_key';
    const string SETTING_LIST_ID = self::LIST_ID;

    /**
     * Get the lists in HTML format.
     * @return string
     */
    public function getListsHtml(): string
    {
        $html = esc_html__('Error connecting to MailChimp.', 'email-download');
        $response = $this->getLists();

        if (isset($response['lists'])) {
            $html = '<ul style="margin: 0">';
            foreach ($response['lists'] as $list) {
                $html .= "<li>{$list['name']} ({$list['id']})</li>";
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Get the lists in array format.
     * @param bool $force Force refresh.
     * @return array
     */
    public function getListsArray(bool $force = false): array
    {
        $data = [];
        $response = $this->getLists($force);

        if (isset($response['lists'])) {
            foreach ($response['lists'] as $list) {
                $data[$list['id']] = $list['name'];
            }
        }

        return $data;
    }

    /**
     * Get the lists in object format.
     * @param bool $force Force refresh.
     * @return array
     */
    public function getListsJson(bool $force = false): array
    {
        $data = [];
        $response = $this->getLists($force);

        if (isset($response['lists'])) {
            foreach ($response['lists'] as $list) {
                $data[] = ['id' => $list['id'], 'name' => $list['name']];
            }
        }

        return $data;
    }

    /**
     * @param bool $force Force refresh.
     * @return array
     */
    protected function getLists(bool $force = false): array
    {
        try {
            $reflection = new ReflectionClass(parent::class);
        } catch (Exception) {
            return [];
        }
        $api_key = $reflection->getProperty('api_key');
        $transient = sprintf('dwnload/mailchimp_lists_%s', base64_encode($api_key->getValue($this)));

        if (($response = get_transient($transient)) === false) {
            try {
                $response = $this->get('lists');
                if (is_array($response) && $force === false) {
                    set_transient($transient, $response, DAY_IN_SECONDS);
                }
            } catch (Exception) {
                return [];
            }
        }

        return $response;
    }
}
