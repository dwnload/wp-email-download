<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;
use Dwnload\WpEmailDownload\ShortcodeApi\ShortcodeInterface;

class Shortcode implements ShortcodeInterface
{

    /** @var string $tag */
    protected string $tag;

    /**
     * EmailDownloadShortcode constructor.
     * @param string $tag
     * @param ShortcodeHandler $handler
     */
    public function __construct(string $tag, protected ShortcodeHandler $handler)
    {
        $this->tag = $tag;
        $this->handler->setTag($tag);
        if (method_exists($this->handler, 'pluginsLoaded')) {
            $this->handler->pluginsLoaded();
        }
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @return ShortcodeHandler
     */
    public function getHandler(): ShortcodeHandler
    {
        return $this->handler;
    }
}
