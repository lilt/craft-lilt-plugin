<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
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
        $jobLilt = Craftliltplugin::getInstance()->liltJobRepository->create(
            $job->title . ' | {today}'
        );

        $elementIdsToTranslate = $job->getElementIds();

        $targetLanguages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            $job->getTargetSiteIds()
        );

        $files = [];

        foreach ($elementIdsToTranslate as $entry) {
            $element = Craft::$app->elements->getElementById($entry, null, $job->sourceSiteId);

            if(!$element) {
                //TODO: handle
                continue;
            }

            $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $element
            );

            $files[] = $this->createJobFile(
                $content,
                $entry,
                $jobLilt->getId(),
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int) $job->sourceSiteId),
                $targetLanguages
            );
        }

        $job->files = json_encode($files);
        $job->liltJobId = $jobLilt->getId();
        $job->status = Job::STATUS_IN_PROGRESS;

        //TODO: check how it works
        Craft::$app->getElements()->saveElement($job, true, true, true);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        $jobRecord->files = $job->files;
        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->liltJobId = $jobLilt->getId();

        $jobRecord->update();
        Craft::$app->getCache()->flush();
        Craftliltplugin::getInstance()->liltJobRepository->start($jobLilt->getId());
    }

    private function createJobFile(
        array $content,
        int $entryId,
        int $jobId,
        string $sourceLanguage,
        array $targetSiteLanguages
    ): string {
        $file = $this->createTranslateFile(
            $jobId,
            $content
        );

        if (!file_exists($file)) {
            throw new \RuntimeException("File {$file} not exist!");
        }

        Craftliltplugin::getInstance()->liltJobsFileRepository->addFileToJob(
            $jobId,
            'entry_' . $entryId . '.json+html',
            file_get_contents($file),
            $sourceLanguage,
            $targetSiteLanguages,
            (new DateTime())->setTimestamp(strtotime('+2 weeks'))
        );

        return $file;
    }

    private function createTranslateFile(int $id, array $content): string
    {
        $tempPath = Craft::$app->path->getTempPath();
        $fileName = sprintf(
            'lilt-translate-file-%d-%s.json',
            $id,
            date('Y-m-d')
        );

        $contentString = json_encode($content);

        file_put_contents($tempPath . '/' . $fileName, $contentString);

        return $tempPath . '/' . $fileName;
    }
}