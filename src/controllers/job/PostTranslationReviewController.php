<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;
use yii\web\Response;

class PostTranslationReviewController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws Throwable
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();
        $translationId = $request->getParam('translationId');

        if (!$request->getIsPost()) {
            return (new Response())->setStatusCode(405);
        }

        if (empty($translationId)) {
            return (new Response())->setStatusCode(400);
        }

        $translation = Craftliltplugin::getInstance()->translationRepository->findOneById((int) $translationId);

        $updated = TranslationRecord::updateAll(
            ['status' => TranslationRecord::STATUS_READY_TO_PUBLISH],
            ['id' => $translation->id]
        );

        if ($updated) {

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $translation->jobId,
                Craft::$app->getUser()->getId(),
                sprintf('Translation (id: %d) reviewed', $translation->id)
            );

            $readyToPublish = true;

            $jobRecord = JobRecord::findOne(['id' => $translation->jobId]);
            $translations = Craftliltplugin::getInstance()->translationRepository->findByJobId($translation->jobId);
            foreach ($translations as $translation) {
                if ($translation->status !== TranslationRecord::STATUS_READY_TO_PUBLISH) {
                    $readyToPublish = false;
                    break;
                }
            }

            if ($readyToPublish) {
                $jobRecord->status = Job::STATUS_READY_TO_PUBLISH;
                $jobRecord->save();

                Craftliltplugin::getInstance()->jobLogsRepository->create(
                    $jobRecord->id,
                    Craft::$app->getUser()->getId(),
                    'Job reviewed'
                );

                Craft::$app->elements->invalidateCachesForElementType(
                    Job::class
                );
            }

            /*
            Queue::push((new UpdateJobStatusOnTranslationChange(
                [
                    'jobId' => $translation->jobId,
                ]
            )));
            */
        }

        if ($updated !== 1) {
            //TODO: handle when we update more then one row
        }

        return $this->asJson([
            'success' => $updated === 1
        ]);
    }
}
