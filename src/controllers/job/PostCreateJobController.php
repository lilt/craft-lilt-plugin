<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\services\job\CreateJob;
use yii\base\InvalidConfigException;
use yii\web\Response;

class PostCreateJobController extends Controller
{
    protected $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsPost()) {
            return (new Response())->setStatusCode(405);
        }

        $bodyParams = $this->request->getBodyParams();

        if (empty($bodyParams['entries']) || empty($bodyParams['title']) || empty ($bodyParams['targetSitesLanguages'])) {
            return (new Response())->setStatusCode(400);
        }

        $entries = json_decode($bodyParams['entries'], false) ?? [];

        if (empty($entries)) {
            return (new Response())->setStatusCode(400);
        }

        $command = new CreateJob(
            $bodyParams['title'],
            $entries,
            $bodyParams['targetSitesLanguages']
        );
        Craftliltplugin::getInstance()->createJobHandler->__invoke(
            $command
        );

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Translate job created successfully.'
        );

        return $this->redirect('admin/craft-lilt-plugin/jobs', 302, true);
    }
}