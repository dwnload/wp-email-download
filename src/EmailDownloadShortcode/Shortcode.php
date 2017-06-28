<?php

namespace Dwnload\WpEmailDownload\EmailDownloadShortcode;

use Dwnload\WpEmailDownload\ShortcodeApi\Handler\ShortcodeHandler;
use Dwnload\WpEmailDownload\ShortcodeApi\ShortcodeInterface;

class Shortcode implements ShortcodeInterface {

    /**
     * @var string $tag
     */
    protected $tag;

    /**
     * @var ShortcodeHandler $handler
     */
    protected $handler;

    /**
     * EmailDownloadShortcode constructor.
     *
     * @param string $tag
     * @param ShortcodeHandler $handler
     */
    public function __construct( string $tag, ShortcodeHandler $handler ) {
        $this->tag = $tag;
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getTag(): string {
        return $this->tag;
    }

    /**
     * @return ShortcodeHandler
     */
    public function getHandler(): ShortcodeHandler {
        return $this->handler;
    }
}
