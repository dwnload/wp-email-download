<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\ShortcodeApi\AbstractShortcode;
use Dwnload\WpEmailDownload\ShortcodeApi\ShortcodeInterface;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class ShortcodeRegistration
 * @package Dwnload\WpEmailDownload\ShortcodeApi\EmailDownloadShortcode
 */
class ShortcodeRegistration extends AbstractShortcode implements WpHooksInterface
{

    /**
     * ShortcodeRegistration constructor.
     * @param ShortcodeInterface $shortcode
     */
    public function __construct(protected ShortcodeInterface $shortcode)
    {
    }

    public function addHooks(): void
    {
        add_action('init', [$this, 'addShortcode']);
    }

    public function addShortcode(): void
    {
        add_shortcode($this->shortcode->getTag(), [$this->shortcode->getHandler(), 'handler']);
    }
}
