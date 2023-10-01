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

use Codeception\Module;
use Craft;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\I18NRecord;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\CreateDraftCommand;
use lilthq\craftliltplugin\services\handlers\commands\CreateJobCommand;
use yii\base\InvalidArgumentException;

class CraftLiltPluginHelper extends Module
{
    public function createJobWithTranslations(array $data): array
    {
        $job = $this->createJob($data);

//        if (empty($data['liltJobId'])) {
//            throw new \RuntimeException('Please provide liltJobId for your test');
//        }

        //TODO: DRY here?
        $elementIdsToTranslate = $job->getElementIds();

        foreach ($elementIdsToTranslate as $elementId) {
            $versionId = $job->getElementVersionId($elementId);

            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);
            $drafts = [];
            $contents = [];
            foreach ($job->getTargetSiteIds() as $targetSiteId) {
                $contents[$targetSiteId] = Craftliltplugin::getInstance()
                    ->elementTranslatableContentProvider
                    ->provide($element);
                //Create draft with & update all values to source element
                $drafts[$targetSiteId] = Craftliltplugin::getInstance()->createDraftHandler->create(
                    new CreateDraftCommand(
                        $element,
                        $job->title,
                        $job->sourceSiteId,
                        $targetSiteId,
                        $job->translationWorkflow,
                        $job->authorId
                    )
                );
            }

            $createTranslationsResult = Craftliltplugin::getInstance()
                ->createTranslationsHandler
                ->__invoke(
                    $job,
                    $contents,
                    $elementId,
                    $versionId,
                    $drafts
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
            $data['targetSiteIds'] = Craftliltplugin::getInstance()->languageMapper->getLanguageToSiteId();
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

        //TODO: looks like same logic & job->status doesn't have any affect
        if (!empty($data['status'])) {
            $job->status = $data['status'];
            $record = JobRecord::findOne(['id' => $job->id]);
            $record->status = $data['status'];
            $record->save();
        }

        if (!empty($data['liltJobId'])) {
            $job->liltJobId = $data['liltJobId'];
            $record = JobRecord::findOne(['id' => $job->id]);
            $record->liltJobId = $data['liltJobId'];
            $record->save();
        }

        return $job;
    }

    public function assertJobInQueue(BaseJob $expectedJob): void
    {
        $jobInfos = $this->getJobInfos();

        $key = get_class($expectedJob) . '_' . json_encode($expectedJob);

        $this->assertArrayHasKey($key, $jobInfos);
        $this->assertEquals($expectedJob, $jobInfos[$key]);
    }

    public function assertJobNotInQueue(BaseJob $expectedJob, string $message = ''): void
    {
        $jobInfos = $this->getJobInfos();

        $key = get_class($expectedJob) . '_' . json_encode($expectedJob);

        $this->assertArrayNotHasKey($key, $jobInfos, $message);
    }

    public function assertJobIdNotInQueue(int $jobId, string $message = ''): void
    {
        $jobInfos = $this->getJobInfos();

        foreach ($jobInfos as $jobInfo) {
            if (property_exists($jobInfo, 'jobId')) {
                $this->assertNotEquals($jobId, $jobInfo->jobId, $message);
            }
        }
    }

    public function assertTranslationsContentMatch(array $translations, array $expectedContent): void
    {
        foreach ($translations as $translation) {
            $translation->refresh();

            $this->assertNotEmpty($translation->translatedDraftId);

            $translatedDraft = Craft::$app->elements->getElementById(
                $translation->translatedDraftId,
                'craft\elements\Entry',
                $translation->targetSiteId
            );

            $appliedContent = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $translatedDraft
            );
            $translationTargetLanguage = Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                $translation->targetSiteId
            );

            //TODO: maybe we can write our own assertion to be sure that ids are correct
            //we definitely can't ignore keys
            $this->assertSame(
                $expectedContent[$translationTargetLanguage],
                $appliedContent
            );
        }
    }

    public function setEnableSplitJobFileUpload(int $value): void
    {
        $settingRecord = SettingRecord::findOne(['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]);
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]);
        }

        $settingRecord->value = $value;
        $settingRecord->save();
    }

    public function assertI18NRecordsExist(int $targetSiteId, array $expectedTranslations): void
    {
        $actualRecords = Craftliltplugin::getInstance()->i18NRepository->findAllByTargetSiteId($targetSiteId);

        $actualTranslations = array_map(static function (I18NRecord $i18NRecord) {
            return $i18NRecord->target;
        }, $actualRecords);

        $diff = array_diff(array_values($expectedTranslations), $actualTranslations);

        $this->assertEmpty($diff);
    }

    public function assertTranslationContentMatch(
        int $jobId,
        int $elementId,
        string $targetLanguage,
        array $expectedContent,
        int $connectorTranslationId,
        string $status = TranslationRecord::STATUS_READY_FOR_REVIEW
    ): void {
        $translation = TranslationRecord::findOne([
            'jobId' => $jobId,
            'elementId' => $elementId,
            'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage),
        ]);

        if ($translation === null) {
            $this->fail('Translation not found');
        }

        $translation->refresh();

        $this->assertNotEmpty($translation->translatedDraftId);

        $translatedDraft = Craft::$app->elements->getElementById(
            $translation->translatedDraftId,
            'craft\elements\Entry',
            $translation->targetSiteId
        );

        if ($translatedDraft === null) {
            $this->fail('Draft not found');
        }

        $appliedContent = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
            $translatedDraft
        );
        $this->assertSame($connectorTranslationId, $translation->connectorTranslationId);
        $this->assertSame($status, $translation->status);

        //TODO: maybe we can write our own assertion to be sure that ids are correct
        //we definitely can't ignore keys
        $this->assertEqualsCanonicalizing(
            $expectedContent,
            $appliedContent
        );
    }

    public function assertTranslationFailed(
        int $jobId,
        int $elementId,
        string $targetLanguage,
        int $connectorTranslationId
    ): void {
        $translation = TranslationRecord::findOne(
            [
                'jobId' => $jobId,
                'elementId' => $elementId,
                'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage),
            ]
        );

        if ($translation === null) {
            $this->fail('Translation not found');
        }

        $translation->refresh();

        $this->assertNotEmpty($translation->translatedDraftId);
        $this->assertEmpty($translation->targetContent);
        $this->assertSame($connectorTranslationId, $translation->connectorTranslationId);
        $this->assertSame(TranslationRecord::STATUS_FAILED, $translation->status);
    }

    public function assertTranslationStatus(int $translationId, string $expectedStatus): void
    {
        $actualTranslation = TranslationRecord::findOne(
            [
                'id' => $translationId
            ]
        );

        $this->assertSame($expectedStatus, $actualTranslation->status);
    }

    public function assertJobStatus(int $jobId, string $expectedStatus): void
    {
        $actualJob = JobRecord::findOne(
            [
                'id' => $jobId
            ]
        );

        $this->assertSame($expectedStatus, $actualJob->status);
    }

    public function setTranslationStatus(
        int $jobId,
        int $elementId,
        string $targetLanguage,
        string $status
    ): TranslationRecord {
        $translation = TranslationRecord::findOne(
            [
                'jobId' => $jobId,
                'elementId' => $elementId,
                'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage),
            ]
        );

        $this->assertInstanceOf(TranslationRecord::class, $translation);

        $translation->status = $status;
        $translation->save();

        return $translation;
    }

    public function assertTranslationInProgress(
        int $jobId,
        int $elementId,
        string $targetLanguage
    ): void {
        $translation = TranslationRecord::findOne(
            [
                'jobId' => $jobId,
                'elementId' => $elementId,
                'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage),
            ]
        );

        if ($translation === null) {
            $this->fail('Translation not found');
        }

        $translation->refresh();

        $this->assertNotEmpty($translation->translatedDraftId);
        $this->assertEmpty($translation->targetContent);
        $this->assertEmpty($translation->connectorTranslationId);
        $this->assertSame(TranslationRecord::STATUS_IN_PROGRESS, $translation->status);
    }

    /**
     * We need to override craft runQueue, since there is a bug
     *
     * https://github.com/craftcms/cms/blob/3.7.0/src/test/Craft.php#L549
     *
     * Solution is to use \Craft instead of craft\test\Craft
     *
     * Fixed in 3.7.33
     * https://github.com/craftcms/cms/commit/d0a2e728ce9a7540d4a3844aa6d987249a31d9c0
     *
     */
    public function runQueue(string $queueItem, array $params = []): void
    {
        /** @var BaseJob $job */
        $job = new $queueItem($params);

        if (!$job instanceof BaseJob) {
            throw new InvalidArgumentException('Not a job');
        }

        Craft::$app->getQueue()->push($job);

        Craft::$app->getQueue()->run();
    }

    public function executeQueue(string $queueItem, array $params = []): void
    {
        /** @var BaseJob $job */
        $job = new $queueItem($params);

        if (!$job instanceof BaseJob) {
            throw new InvalidArgumentException('Not a job');
        }

        $job->execute(
            Craft::$app->queue
        );
    }

    /**
     * @return array
     */
    private function getJobInfos(): array
    {
        $jobInfos = Craft::$app->queue->getJobInfo();

        $this->assertNotEmpty($jobInfos);

        $return = [];

        foreach ($jobInfos as $jobInfo) {
            $actual = Craft::$app->queue->getJobDetails($jobInfo['id']);
            $key = get_class($actual['job']) . '_' . json_encode($actual['job']);
            $return[$key] = $actual['job'];
        }
        return $return;
    }
}
