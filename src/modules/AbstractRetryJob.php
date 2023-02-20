<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use craft\queue\BaseJob;

abstract class AbstractRetryJob extends BaseJob
{
    /**
     * @var int
     */
    public $jobId;

    /**
     * @var int
     */
    public $attempt = 0;

    /**
     *
     * Is current job is eligible for retry
     *
     * @return bool
     */
    abstract public function canRetry(): bool;
    abstract public function getRetryJob(): BaseJob;
}
