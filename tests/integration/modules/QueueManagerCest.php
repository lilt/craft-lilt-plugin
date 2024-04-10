<?php

declare(strict_types=1);

namespace integration\modules;

use Codeception\Exception\ModuleException;
use Craft;
use craft\elements\Entry;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\ManualJobSync;
use lilthq\craftliltplugin\modules\QueueManager;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;

//TODO: add expectation of body request
class QueueManagerCest extends AbstractIntegrationCest
{
    public function _before(IntegrationTester $I): void
    {
        parent::_before($I);

        JobRecord::deleteAll();
    }

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ]
        ];
    }

    /**
     * @throws ModuleException
     */
    public function testRunSuccess(IntegrationTester $I): void
    {
        $I->clearQueue();
        Craft::$app->getMutex()->release(
            QueueManager::getMutexKey()
        );

        $I->disableOption(SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $firstJobStatusInProgress = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_IN_PROGRESS
        ]);

        $secondJobStatusInProgress = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_IN_PROGRESS
        ]);

        $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_COMPLETE
        ]);

        $I->executeQueue(QueueManager::class);

        $jobInfos = Craft::$app->queue->getJobInfo();

        Assert::assertNotEmpty($jobInfos);

        $I->assertJobInQueue(
            new ManualJobSync(
                ['jobIds' => [
                    $firstJobStatusInProgress->id,
                    $secondJobStatusInProgress->id
                ]]
            )
        );
    }

    /**
     * @throws ModuleException
     */
    public function testRunSuccessDisableAutomaticSync(IntegrationTester $I): void
    {
        $I->clearQueue();
        Craft::$app->getMutex()->release(
            QueueManager::getMutexKey()
        );

        $I->enableOption(SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_IN_PROGRESS
        ]);

        $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_IN_PROGRESS
        ]);

        $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'status' => Job::STATUS_COMPLETE
        ]);

        $I->executeQueue(QueueManager::class);

        $jobInfos = Craft::$app->queue->getJobInfo();

        Assert::assertEmpty($jobInfos);

        // set option back for next tests
        $I->disableOption(SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC);
    }
}
