<?php
/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

namespace Helper;

use craft\queue\BaseJob;
use \lilthq\craftliltplugin\Craftliltplugin;
use Codeception\Module;
use Craft;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\job\CreateJobCommand;

class CraftLiltPluginHelper extends Module
{
    public function createJobWithTranslations(array $data): array
    {
        $job = $this->createJob($data);

        if(empty($data['liltJobId'])) {
            throw new \RuntimeException('Please provide liltJobId for your test');
        }

        //TODO: DRY here?
        $elementIdsToTranslate = $job->getElementIds();
        foreach ($elementIdsToTranslate as $elementId) {
            $versionId = $job->getElementVersionId($elementId);

            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);
            $content = Craftliltplugin::getInstance()
                ->elementTranslatableContentProvider
                ->provide($element);

            $createTranslationsResult = Craftliltplugin::getInstance()
                ->createTranslationsHandler
                ->__invoke(
                    $job,
                    $content,
                    $elementId,
                    $versionId
                );

            if (!$createTranslationsResult) {
                throw new \RuntimeException('Translations not created, upload failed');
            }
        }

        $translations = TranslationRecord::findAll(['jobId' => $job->id]);

        return [$job, $translations];
    }
    public function createJob(array $data): Job
    {
        if ($data['targetSiteIds'] === '*') {
            $data['targetSiteIds'] = Craftliltplugin::getInstance(
            )->languageMapper->getLanguageToSiteId();
        }

        $createJobCommand = new CreateJobCommand(
            $data['title'],
            $data['elementIds'],
            $data['targetSiteIds'],
            $data['sourceSiteId'],
            $data['translationWorkflow'],
            $data['versions'],
            $data['authorId']
        );

        $job = Craftliltplugin::getInstance()->createJobHandler->__invoke(
            $createJobCommand
        );

        if (!empty($data['liltJobId'])) {
            $job->liltJobId = $data['liltJobId'];
            $record = JobRecord::findOne(['id' => $job->id]);
            $record->liltJobId = $data['liltJobId'];
            $record->save();
        }

        return $job;
    }

    public function assertJobInQueue(BaseJob $expectedJob): void {
        $jobInfo = Craft::$app->queue->getJobInfo();
        $actual = Craft::$app->queue->getJobDetails($jobInfo[0]['id']);
        $this->assertEquals($expectedJob, $actual['job']);
    }
}
