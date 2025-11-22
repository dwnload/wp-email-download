<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Blocks;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use function dirname;
use function register_block_type;

/**
 * Class EmailDownload
 * @package Dwnload\WpEmailDownload\Blocks
 */
class EmailDownload implements WpHooksInterface
{

    use HooksTrait;

    public function addHooks(): void
    {
        $this->addAction('init', [$this, 'registerBlock']);
    }

    /**
     * Registers the block using the metadata loaded from the `block.json` file.
     * Behind the scenes, it registers also all assets so they can be enqueued
     * through the block editor in the corresponding context.
     * @see https://developer.wordpress.org/reference/functions/register_block_type/
     */
    protected function registerBlock(): void
    {
        register_block_type(dirname(__DIR__, 2) . '/build/');
    }
}
