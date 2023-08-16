<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Exception\ModuleException;
use Craft;
use craft\elements\Entry;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\ManualJobSync;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;

class PostSyncControllerCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ]
        ];
    }

    /**
     * @throws \craft\errors\InvalidFieldException
     * @throws ModuleException
     */
    public function testSyncSuccess(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
         * @var Job $job777
         * @var TranslationRecord[] $translations777
         */
        [$job777, $translations777] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
            'status' => Job::STATUS_FAILED
        ]);

        /**
         * @var Job $job888
         * @var TranslationRecord[] $translations888
         */
        [$job888, $translations888] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 888,
            'status' => Job::STATUS_READY_FOR_REVIEW
        ]);

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_PATH
            ),
            [
                'jobIds' => [$job777->id, $job888->id]
            ]
        );

        $I->seeResponseCodeIs(200);

        $I->assertJobStatus($job777->id, Job::STATUS_IN_PROGRESS);
        $I->assertJobStatus($job888->id, Job::STATUS_IN_PROGRESS);

        $I->assertTranslationStatus($translations777[0]->id, Job::STATUS_IN_PROGRESS);
        $I->assertTranslationStatus($translations888[0]->id, Job::STATUS_IN_PROGRESS);

        $jobs = [$job888->id, $job777->id];
        sort($jobs);

        $I->assertJobInQueue(
            (new ManualJobSync(
                [
                    'jobIds' => $jobs,
                ]
            ))
        );
    }

    public function testSyncJobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_PATH
            ),
            [
                123,
                456,
                789
            ]
        );

        $I->seeResponseCodeIs(400);
    }

    public function testSyncWrongMethod(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxGetRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_ACTION
            )
        );

        $I->seeResponseCodeIs(404);
    }
}
