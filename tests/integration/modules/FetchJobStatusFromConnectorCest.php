<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Codeception\Exception\ModuleException;
use Craft;
use craft\helpers\Db;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;
use yii\db\Exception;

class FetchJobStatusFromConnectorCest extends AbstractIntegrationCest
{
    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessVerified(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [999],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_COMPLETE
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => $job->liltJobId,
                'jobId'     => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchVerifiedJobTranslationsFromConnector([
                'jobId' => $job->id,
                'liltJobId' => 777
            ])
        );
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessInstant(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [999],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_COMPLETE
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => $job->liltJobId,
                'jobId'     => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchInstantJobTranslationsFromConnector([
                'jobId' => $job->id,
                'liltJobId' => 777
            ])
        );
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessProcessing(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_PROCESSING
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId'     => 100,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'jobId' => 100,
                'liltJobId' => 777
            ])
        );
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessQueued(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_QUEUED
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId'     => 100,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'jobId' => 100,
                'liltJobId' => 777
            ])
        );
    }
}
