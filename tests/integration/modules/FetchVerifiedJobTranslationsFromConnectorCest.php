<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Codeception\Util\HttpCode;
use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\helpers\Db;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use PHPUnit\Framework\Assert;
use yii\db\Exception;

class FetchVerifiedJobTranslationsFromConnectorCest extends AbstractIntegrationCest
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
     */
    public function testExecuteJobNotFound(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 1000,
                'jobId' => 1,
            ]
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );
    }

    /**
     * @throws Exception
     */
    public function testExecuteJobIsInstant(IntegrationTester $I): void
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

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

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );
    }

    private function prepareTestData(IntegrationTester $I): array
    {
        Db::truncateTable(Craft::$app->queue->tableName);

        $user = Craft::$app->getUsers()->getUserById(1);
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
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $I->expectJobGetRequest(777, 200, [
            'status' => JobResponse::STATUS_COMPLETE
        ]);

        $I->expectJobGetRequest(777, 200, [
            'status' => JobResponse::STATUS_COMPLETE
        ]);
        return [$element, $job, $translations];
    }

    public function testExecuteSuccess(IntegrationTester $I): void
    {
        /**
         * @var Entry $element
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$element, $job, $translations] = $this->prepareTestData($I);

        $translationsResponseBody = $this->getTranslationsResponseBody(
            $element->getId(),
            TranslationResponse::STATUS_EXPORT_COMPLETE
        );

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $translationsResponseBody
        );

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'es-ES')
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'de-DE')
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'ru-RU')
        );

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $I->assertTranslationsContentMatch($translations, [
            'es-ES' => ExpectedElementContent::getExpectedBody($element, 'es-ES'),
            'de-DE' => ExpectedElementContent::getExpectedBody($element, 'de-DE'),
            'ru-RU' => ExpectedElementContent::getExpectedBody($element, 'ru-RU'),
        ]);

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        foreach ($translations as $translation) {
            $translation->refresh();

            Assert::assertSame(
                TranslationRecord::STATUS_READY_FOR_REVIEW,
                $translation->status
            );
        }

        Assert::assertSame(
            Job::STATUS_READY_FOR_REVIEW,
            $jobRecord->status
        );
    }

    public function testExecuteOneTranslationFailed(IntegrationTester $I): void
    {
        /**
         * @var Entry $element
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$element, $job, $translations] = $this->prepareTestData($I);

        $translationsResponseBody = $this->getTranslationsResponseBodyOneFailed(
            $element->getId()
        );

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $translationsResponseBody
        );

        $expectedContent = $this->getExpectedContent($element);

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'es-ES')
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'de-DE')
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'ru-RU')
        );

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $I->assertTranslationContentMatch(
            $job->id,
            $element->id,
            'es-ES',
            ExpectedElementContent::getExpectedBody($element, 'es-ES'),
            703695
        );

        $I->assertTranslationContentMatch(
            $job->id,
            $element->id,
            'de-DE',
            ExpectedElementContent::getExpectedBody($element, 'de-DE'),
            703696
        );

        $I->assertTranslationFailed(
            $job->id,
            $element->id,
            'ru-RU',
            703697
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        Assert::assertSame(
            Job::STATUS_READY_FOR_REVIEW,
            $jobRecord->status
        );
    }

    public function testExecuteOneTranslationUnexpectedResponse(IntegrationTester $I): void
    {
        /**
         * @var Entry $element
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$element, $job, $translations] = $this->prepareTestData($I);

        $translationsResponseBody = $this->getTranslationsResponseBody(
            $element->getId(),
            TranslationResponse::STATUS_EXPORT_COMPLETE
        );

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $translationsResponseBody
        );

        $expectedContent = $this->getExpectedContent($element);

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'es-ES')
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody($element, 'ru-RU')
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::INTERNAL_SERVER_ERROR
        );

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $I->assertTranslationContentMatch(
            $job->id,
            $element->id,
            'es-ES',
            ExpectedElementContent::getExpectedBody($element, 'es-ES'),
            703695
        );

        $I->assertTranslationContentMatch(
            $job->id,
            $element->id,
            'ru-RU',
            ExpectedElementContent::getExpectedBody($element, 'ru-RU'),
            703697
        );

        $I->assertTranslationFailed(
            $job->id,
            $element->id,
            'de-DE',
            703696
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        Assert::assertSame(
            Job::STATUS_READY_FOR_REVIEW,
            $jobRecord->status
        );
    }

    public function testExecuteInProgress(IntegrationTester $I): void
    {
        /**
         * @var Entry $element
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$element, $job, $translations] = $this->prepareTestData($I);

        $translationsResponseBody = $this->getTranslationsResponseBody(
            $element->getId(),
            TranslationResponse::STATUS_IMPORT_COMPLETE
        );

        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $translationsResponseBody
        );

        $I->runQueue(
            FetchVerifiedJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $I->assertTranslationInProgress(
            $job->id,
            $element->id,
            'es-ES'
            //, 703695
        );

        $I->assertTranslationInProgress(
            $job->id,
            $element->id,
            'ru-RU'
            //, 703697
        );

        $I->assertTranslationInProgress(
            $job->id,
            $element->id,
            'de-DE'
            //, 703696
        );

        $I->assertJobInQueue(
            new FetchVerifiedJobTranslationsFromConnector([
                'jobId' => $job->id,
                'liltJobId' => 777
            ])
        );

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        Assert::assertSame(
            Job::STATUS_IN_PROGRESS,
            $jobRecord->status
        );
    }

    private function getExpectedContent(Entry $element): array
    {
        /**
         * @var MatrixBlockQuery $matrixField
         */
        $matrixField = $element->getFieldValue('matrix');
        /**
         * @var MatrixBlock[] $blockElements
         */
        $blocks = $matrixField->all();
        $blocksMap = [];
        foreach ($blocks as $block) {
            $blocksMap[$block->type->handle] = $block->id;
        }

        return [
            'de-DE' => [
                $element->getId() => [
                    'title' => 'de-DE: Some example title',
                    'redactor' => 'de-DE: <h1>Here is some header text</h1> Here is some content',
                    'matrix' => [
                        $blocksMap['firstBlock'] => [
                            'fields' => [
                                'plainTextFirstBlock' => 'de-DE: Some text',
                            ],
                        ],
                        $blocksMap['secondblock'] => [
                            'fields' => [
                                'plainTextSecondBlock' => 'de-DE: Some text',
                            ],
                        ],
                    ],
                ],
            ],
            'es-ES' => [
                $element->getId() => [
                    'title' => 'es-ES: Some example title',
                    'redactor' => 'es-ES: <h1>Here is some header text</h1> Here is some content',
                    'matrix' => [
                        $blocksMap['firstBlock'] => [
                            'fields' => [
                                'plainTextFirstBlock' => 'es-ES: Some text',
                            ],
                        ],
                        $blocksMap['secondblock'] => [
                            'fields' => [
                                'plainTextSecondBlock' => 'es-ES: Some text',
                            ],
                        ],
                    ],
                ],
            ],
            'ru-RU' => [
                $element->getId() => [
                    'title' => 'ru-RU: Some example title',
                    'redactor' => 'ru-RU: <h1>Here is some header text</h1> Here is some content',
                    'matrix' => [
                        $blocksMap['firstBlock'] => [
                            'fields' => [
                                'plainTextFirstBlock' => 'ru-RU: Some text',
                            ],
                        ],
                        $blocksMap['secondblock'] => [
                            'fields' => [
                                'plainTextSecondBlock' => 'ru-RU: Some text',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getTranslationsResponseBody(
        int $elementId,
        string $status
    ): array {
        $fileName = sprintf('497058_element_%d.json+html', $elementId);

        $translationsResponseBody = [
            'limit' => 25,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703695,
                    'name' => $fileName,
                    'status' => $status,
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703696,
                    'name' => $fileName,
                    'status' => $status,
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703697,
                    'name' => $fileName,
                    'status' => $status,
                    'trgLang' => 'ru',
                    'trgLocale' => 'RU',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ],
            'start' => 0,
        ];
        return $translationsResponseBody;
    }

    private function getTranslationsResponseBodyOneFailed(int $elementId): array
    {
        $fileName = sprintf('497058_element_%d.json+html', $elementId);

        $translationsResponseBody = [
            'limit' => 25,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703695,
                    'name' => $fileName,
                    'status' => TranslationResponse::STATUS_EXPORT_COMPLETE,
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703696,
                    'name' => $fileName,
                    'status' => TranslationResponse::STATUS_EXPORT_COMPLETE,
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703697,
                    'name' => $fileName,
                    'status' => TranslationResponse::STATUS_EXPORT_FAILED,
                    'trgLang' => 'ru',
                    'trgLocale' => 'RU',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ],
            'start' => 0,
        ];
        return $translationsResponseBody;
    }
}
