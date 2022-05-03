<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;
use yii\web\Response;

class GetSendToLiltController extends Controller
{
    protected $allowAnonymous = false;

    /**
     * @throws ElementNotFoundException
     * @throws Throwable
     * @throws MissingComponentException
     * @throws ApiException
     * @throws StaleObjectException
     * @throws Exception
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        $jobId = (int) $request->getSegment(4);
        $job = Job::findOne(['id' => $jobId]);

        if (!$job) {
            return (new Response())->setStatusCode(404);
        }

        Craftliltplugin::getInstance()->sendJobToLiltConnectorHandler->__invoke($job);

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Job uploaded to Lilt platform successfully'
        );

        return $this->redirect('admin/craft-lilt-plugin/jobs');
    }
}