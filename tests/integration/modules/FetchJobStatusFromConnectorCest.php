<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\Entry;
use craft\helpers\Db;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\db\Exception;

class FetchJobStatusFromConnectorCest extends AbstractIntegrationCest
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
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessVerified(IntegrationTester $I): void
    {
        $I->clearQueue();

        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
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

        $responseBody = [
            'limit' => 25,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 11111,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'export_complete',
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 22222,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'export_complete',
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 33333,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'export_complete',
                    'trgLang' => 'ru',
                    'trgLocale' => 'RU',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ],
            'start' => 0,
        ];

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $responseBody
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => $job->liltJobId,
                'jobId' => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(3, $totalJobs);
        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[0]->id,
                'liltJobId' => 777
            ])
        );

        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[1]->id,
                'liltJobId' => 777
            ])
        );

        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[2]->id,
                'liltJobId' => 777
            ])
        );

        $translationAssertions = [
            'es-ES' => 11111,
            'de-DE' => 22222,
            'ru-RU' => 33333,
        ];
        foreach ($translationAssertions as $language => $expectedConnectorTranslationId) {
            $translationEs = TranslationRecord::findOne([
                'jobId' => $job->id,
                'elementId' => $element->id,
                'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($language)
            ]);

            Assert::assertSame($expectedConnectorTranslationId, $translationEs->connectorTranslationId);
        }
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessInstant(IntegrationTester $I): void
    {
        $I->clearQueue();

        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

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

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_COMPLETE
            ]
        );

        $responseBody = [
            'limit' => 25,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 11111,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'mt_complete',
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 22222,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'mt_complete',
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 33333,
                    'name' => sprintf('497058_element_%d_first-entry-user-1.json+html', $element->id),
                    'status' => 'mt_complete',
                    'trgLang' => 'ru',
                    'trgLocale' => 'RU',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ],
            'start' => 0,
        ];

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $responseBody
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => $job->liltJobId,
                'jobId' => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(3, $totalJobs);
        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[0]->id,
                'liltJobId' => 777
            ])
        );

        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[1]->id,
                'liltJobId' => 777
            ])
        );

        $I->assertJobInQueue(
            new FetchTranslationFromConnector([
                'jobId' => $job->id,
                'translationId' => $translations[2]->id,
                'liltJobId' => 777
            ])
        );

        $translationAssertions = [
            'es-ES' => 11111,
            'de-DE' => 22222,
            'ru-RU' => 33333,
        ];
        foreach ($translationAssertions as $language => $expectedConnectorTranslationId) {
            $translationEs = TranslationRecord::findOne([
                'jobId' => $job->id,
                'elementId' => $element->id,
                'targetSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($language)
            ]);

            Assert::assertSame($expectedConnectorTranslationId, $translationEs->connectorTranslationId);
        }
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteJobNotFound(IntegrationTester $I): void
    {
        $I->clearQueue();

        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => 100,
            ]
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );
    }

    /**
     * @throws Exception
     * @throws ModuleException
     */
    public function testExecuteSuccessProcessing(IntegrationTester $I): void
    {
        $I->clearQueue();

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
                'status' => JobResponse::STATUS_PROCESSING
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'jobId' => $job->id,
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
        $I->clearQueue();

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
                'status' => JobResponse::STATUS_QUEUED
            ]
        );

        $I->runQueue(
            FetchJobStatusFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $totalJobs = Craft::$app->queue->getJobInfo();

        Assert::assertCount(1, $totalJobs);
        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'jobId' => $job->id,
                'liltJobId' => 777
            ])
        );
    }
}
