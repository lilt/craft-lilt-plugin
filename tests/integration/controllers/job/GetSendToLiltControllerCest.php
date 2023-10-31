<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Craft;
use craft\elements\Entry;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;

class GetSendToLiltControllerCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ]
        ];
    }

    public function testSendToLilt(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
        ]);

        $expectQueueJob = new SendJobToConnector(['jobId' => $job->id]);

        $I->stopFollowingRedirects();
        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                $job->id
            )
        );

        $jobActual = Job::findOne(['id' => $job->id]);

        Assert::assertSame(Job::STATUS_IN_PROGRESS, $jobActual->status);

        $I->canSeeResponseCodeIsRedirection();
        $I->seeHeader(
            'location',
            sprintf('https://localhost/index.php?p=admin/craft-lilt-plugin/job/edit/%d', $job->id)
        );

        $I->assertJobInQueue($expectQueueJob);
    }

    public function testSendToLilt_InProgress(IntegrationTester $I, $scenario): void
    {
        $scenario->skip('queue manager is pushed, need to fix test');

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_IN_PROGRESS
        ]);

        $expectQueueJob = new SendJobToConnector(['jobId' => $job->id]);

        $I->stopFollowingRedirects();
        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                $job->id
            )
        );

        $jobActual = Job::findOne(['id' => $job->id]);

        Assert::assertSame(Job::STATUS_IN_PROGRESS, $jobActual->status);

        $I->canSeeResponseCodeIsRedirection();
        $I->seeHeader(
            'location',
            sprintf('https://localhost/index.php?p=admin/craft-lilt-plugin/job/edit/%d', $job->id)
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );
    }

    public function testSendToLilt_JobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                123123
            )
        );

        $I->seeResponseCodeIs(404);
    }

    public function testSendToLilt_WrongMethod(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                123123
            )
        );

        $I->seeResponseCodeIs(404);
    }

}
