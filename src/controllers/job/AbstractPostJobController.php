<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use lilthq\craftliltplugin\elements\Job;
use yii\base\InvalidConfigException;
use yii\web\MethodNotAllowedHttpException;

abstract class AbstractPostJobController extends AbstractJobController
{
    /**
     * @throws InvalidConfigException
     * @throws MethodNotAllowedHttpException
     */
    protected function getJob(): Job
    {
        $request = $this->request;

        if (!$request->getIsPost()) {
            throw new MethodNotAllowedHttpException('Method not allowed, only POST');
        }

        $job = $this->convertRequestToJobModel();
        $job->validate();

        if ($job->versions === '[]') {
            //TODO: fix FE part?
            $job->versions = [];
        }

        return $job;
    }
}
