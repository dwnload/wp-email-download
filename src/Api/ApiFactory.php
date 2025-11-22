<?php

declare(strict_types=1);

namespace Dwnload\WpEmailDownload\Api;

/**
 * Trait ApiFactory
 * @package Dwnload\WpEmailDownload\Api
 */
trait ApiFactory
{

    /**
     * Trait constructor.
     * @param Api $api
     */
    public function __construct(protected Api $api)
    {
    }
}
