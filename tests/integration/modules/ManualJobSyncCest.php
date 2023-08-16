<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\queue\Queue;
use IntegrationTester;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\ManualJobSync;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;

class ManualJobSyncCest extends AbstractIntegrationCest
{
    public function testNoJobs(IntegrationTester $I): void
    {
        $I->runQueue(
            ManualJobSync::class,
            [
                'jobIds' => [],
            ]
        );

        $jobInfos = Craft::$app->queue->getJobInfo();

        Assert::assertEmpty($jobInfos);
    }

    public function testJobsNotFound(IntegrationTester $I): void
    {
        $I->runQueue(
            ManualJobSync::class,
            [
                'jobIds' => [111, 222, 333, 444, 555, 666],
            ]
        );

        $jobInfos = Craft::$app->queue->getJobInfo();

        Assert::assertEmpty($jobInfos);
    }

    public function testFewJobsNotSent(IntegrationTester $I): void
    {
        $job1 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_DRAFT,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job2 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_NEW,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job3 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_IN_PROGRESS,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job4 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_READY_FOR_REVIEW,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job5 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_READY_TO_PUBLISH,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job6 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_FAILED,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job7 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_COMPLETE,
            'versions' => [],
            'authorId' => 1,
        ]);

        $job8 = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_IN_PROGRESS,
            'liltJobId' => 11111111,
            'versions' => [],
            'authorId' => 1,
        ]);

        $I->executeQueue(
            ManualJobSync::class,
            [
                'jobIds' => [$job1->id, $job2->id, $job3->id, $job4->id, $job5->id, $job6->id],
            ]
        );

        $jobInfos = Craft::$app->queue->getJobInfo();

        Assert::assertNotEmpty($jobInfos);

        $I->assertJobNotInQueue(
            new FetchJobStatusFromConnector(
                ['jobId' => $job1->id]
            ),
            $job1->status
        );

        $I->assertJobIdNotInQueue(
            $job1->id,
            $job1->status
        );

        $I->assertJobNotInQueue(
            new SendJobToConnector(
                ['jobId' => $job2->id]
            ),
            $job2->status
        );

        $I->assertJobIdNotInQueue(
            $job2->id,
            $job2->status
        );

        $I->assertJobInQueue(
            new SendJobToConnector(
                ['jobId' => $job3->id]
            ),
            $job3->status
        );

        $I->assertJobInQueue(
            new SendJobToConnector(
                ['jobId' => $job4->id]
            ),
            $job4->status
        );

        $I->assertJobInQueue(
            new SendJobToConnector(
                ['jobId' => $job5->id]
            ),
            $job5->status
        );

        $I->assertJobInQueue(
            new SendJobToConnector(
                ['jobId' => $job6->id]
            ),
            $job6->status
        );

        $I->assertJobNotInQueue(
            new SendJobToConnector(
                ['jobId' => $job7->id]
            ),
            $job7->status
        );

        $I->assertJobIdNotInQueue(
            $job7->id,
            $job7->status
        );

        $I->assertJobNotInQueue(
            new FetchJobStatusFromConnector(
                ['jobId' => $job8->id]
            ),
            $job8->status . ' with lilt id'
        );
    }

    public function testDelayedJob(IntegrationTester $I): void
    {
        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_IN_PROGRESS,
            'versions' => [],
            'authorId' => 1,
        ]);

        $queue = Craft::$app->getQueue();
        $queueId = $queue
            ->priority(1024)
            ->delay(99999)
            ->ttr(null)
            ->push(
                new FetchJobStatusFromConnector(
                    ['jobId' => $job->id]
                )
            );

        $I->executeQueue(
            ManualJobSync::class,
            [
                'jobIds' => [$job->id],
            ]
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        Assert::assertNotEmpty($jobInfos);

        $I->assertJobInQueue(
            new FetchJobStatusFromConnector(
                ['jobId' => $job->id]
            ),
            $job->status
        );

        $jobDetails = Craft::$app->queue->getJobDetails((string) $queueId);
        Assert::assertEquals(0, $jobDetails['delay']);
    }

    public function testFailedJob(IntegrationTester $I): void
    {
        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [123, 456, 789],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT,
            'status' => Job::STATUS_IN_PROGRESS,
            'versions' => [],
            'authorId' => 1,
        ]);

        $queue = Craft::$app->getQueue();
        $queueId = $queue
            ->priority(1024)
            ->delay(99999)
            ->ttr(null)
            ->push(
                new FetchJobStatusFromConnector(
                    ['jobId' => $job->id]
                )
            );

        Db::update(Table::QUEUE, [
            'fail' => true,
            'error' => 'Fancy error',
        ], [
            'id' => $queueId,
        ], [], false);

        $I->executeQueue(
            ManualJobSync::class,
            [
                'jobIds' => [$job->id],
            ]
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        Assert::assertNotEmpty($jobInfos);

        $I->assertJobInQueue(
            new FetchJobStatusFromConnector(
                ['jobId' => $job->id]
            ),
            $job->status
        );

        $jobDetails = Craft::$app->queue->getJobDetails((string) $queueId);
        Assert::assertEquals(Queue::STATUS_WAITING, $jobDetails['status']);
        Assert::assertEquals(0, $jobDetails['delay']);
    }
}
