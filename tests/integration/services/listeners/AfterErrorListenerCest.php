<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\services\listeners;

use Codeception\Exception\ModuleException;
use craft\elements\Entry;
use Exception;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\listeners\AfterErrorListener;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\queue\ExecEvent;
use IntegrationTester;

class AfterErrorListenerCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ],
        ];
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeNoRetry_FetchJobStatusFromConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new FetchJobStatusFromConnector([
            'jobId' => $job->id,
            'attempt' => 10,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_FAILED);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_FAILED);
        }
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeWithRetry_FetchJobStatusFromConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        Assert::assertNotNull($jobRecord);

        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->save();

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new FetchJobStatusFromConnector([
            'jobId' => $job->id,
            'attempt' => 2,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_IN_PROGRESS);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_IN_PROGRESS);
        }

        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'jobId' => $job->id,
                'attempt' => 3,
            ])
        );
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeNoRetry_FetchTranslationFromConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new FetchTranslationFromConnector([
            'jobId' => $job->id,
            'attempt' => 10,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_FAILED);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_FAILED);
        }
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeWithRetry_FetchTranslationFromConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        Assert::assertNotNull($jobRecord);

        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->save();

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new FetchTranslationFromConnector([
            'jobId' => $job->id,
            'attempt' => 2,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_IN_PROGRESS);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_IN_PROGRESS);
        }

        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'attempt' => 3,
            ])
        );
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeNoRetry_SendJobToConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new SendJobToConnector([
            'jobId' => $job->id,
            'attempt' => 10,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_FAILED);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_FAILED);
        }
    }

    /**
     * @throws ModuleException
     */
    public function testInvokeWithRetry_SendJobToConnector(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        Assert::assertNotNull($jobRecord);

        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->save();

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new SendJobToConnector([
            'jobId' => $job->id,
            'attempt' => 2,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_IN_PROGRESS);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_IN_PROGRESS);
        }

        $I->assertJobInQueue(
            new SendJobToConnector([
                'jobId' => $job->id,
                'attempt' => 3,
            ])
        );
    }
}
