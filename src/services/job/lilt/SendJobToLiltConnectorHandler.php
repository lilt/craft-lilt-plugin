<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\ElementNotFoundException;
use DateTimeInterface;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\records\JobRecord;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

class SendJobToLiltConnectorHandler
{
    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws ApiException
     * @throws Exception
     * @throws StaleObjectException
     */
    public function __invoke(Job $job): void
    {
        $jobLilt = Craftliltplugin::getInstance()->connectorJobRepository->create(
            $job->title . ' | {today}'
        );

        $elementIdsToTranslate = $job->getElementIds();

        $targetLanguages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            $job->getTargetSiteIds()
        );

        $translationRecords = [];

        foreach ($elementIdsToTranslate as $elementId) {
            $element = Craft::$app->elements->getElementById($elementId, null, $job->sourceSiteId);

            if (!$element) {
                //TODO: handle
                continue;
            }

            $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $element
            );

            #$content = Craftliltplugin::getInstance()->expandedContentProvider->provide(
            #    $element
            #);

            $this->createJobFile(
                $content,
                $elementId,
                $jobLilt->getId(),
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$job->sourceSiteId),
                $targetLanguages,
                $job->dueDate
            );

            $translationRecords[] = array_values(
                array_map(
                    static function (int $targetSiteId) use ($job, $content, $elementId) {
                        return new TranslationRecord([
                            'jobId' => $job->id,
                            'elementId' => $elementId,
                            'sourceSiteId' => $job->sourceSiteId,
                            'targetSiteId' => $targetSiteId,
                            'sourceContent' => $content,
                            'status' => TranslationRecord::STATUS_NEW,
                        ]);
                    },
                    $job->getTargetSiteIds()
                )
            );
        }

        $translationRecords = array_merge(...$translationRecords);

        foreach ($translationRecords as $jobElementRecord) {
            /**
             * @var TranslationRecord $jobElementRecord
             */
            $jobElementRecord->save();
        }

        $job->liltJobId = $jobLilt->getId();
        $job->status = Job::STATUS_IN_PROGRESS;

        //TODO: check how it works
        Craft::$app->getElements()->saveElement($job, true, true, true);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->liltJobId = $jobLilt->getId();

        $jobRecord->update();
        Craft::$app->getCache()->flush();
        Craftliltplugin::getInstance()->connectorJobRepository->start($jobLilt->getId());
    }

    private function createJobFile(
        array $content,
        int $entryId,
        int $jobId,
        string $sourceLanguage,
        array $targetSiteLanguages,
        DateTimeInterface $dueDate
    ): void {
        $contentString = json_encode($content);
        Craftliltplugin::getInstance()->connectorJobsFileRepository->addFileToJob(
            $jobId,
            'element_' . $entryId . '.json+html',
            $contentString,
            $sourceLanguage,
            $targetSiteLanguages,
            $dueDate
        );
    }

    #private function createTranslateFile(int $id, array $content): string
    #{
    #    $tempPath = Craft::$app->path->getTempPath();
    #    $fileName = sprintf(
    #        'lilt-translate-file-%d-%s.json',
    #        $id,
    #        date('Y-m-d')
    #    );

    #    $contentString = json_encode($content);

    #    file_put_contents($tempPath . '/' . $fileName, $contentString);

    #    return $tempPath . '/' . $fileName;
    #}
}
