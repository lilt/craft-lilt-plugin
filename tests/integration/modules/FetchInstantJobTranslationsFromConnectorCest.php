<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Codeception\Util\HttpCode;
use Craft;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\helpers\Db;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use PHPUnit\Framework\Assert;
use yii\db\Exception;

class FetchInstantJobTranslationsFromConnectorCest extends AbstractIntegrationCest
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
            FetchInstantJobTranslationsFromConnector::class,
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
    public function testExecuteJobIsVerified(IntegrationTester $I): void
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
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $I->runQueue(
            FetchInstantJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        Assert::assertEmpty(
            Craft::$app->queue->getTotalJobs()
        );
    }

    /**
     * @param IntegrationTester $I
     * @return void
     * @throws InvalidFieldException
     */
    public function testExecuteSuccess(IntegrationTester $I): void
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
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
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

        $translationsResponseBody = $this->getTranslationsResponseBody(
            $element->getId(),
            TranslationResponse::STATUS_MT_COMPLETE
        );

        /**
         * @var TranslationRecord[][]
         */
        $translationsMapped = [];
        foreach ($translations as $translation) {
            $translationsMapped[$translation->elementId][Craftliltplugin::getInstance(
            )->languageMapper->getLanguageBySiteId($translation->targetSiteId)] = $translation;
        }

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
            ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['es-ES']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['es-ES']->targetSiteId
                ),
                'es-ES'
            )
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['de-DE']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['de-DE']->targetSiteId
                ),
                'de-DE'
            )
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['ru-RU']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['ru-RU']->targetSiteId
                ),
                'ru-RU'
            )
        );

        $I->runQueue(
            FetchInstantJobTranslationsFromConnector::class,
            [
                'liltJobId' => 777,
                'jobId' => $job->id,
            ]
        );

        $I->assertTranslationsContentMatch($translations, [
            'es-ES' => ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['es-ES']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['es-ES']->targetSiteId
                ),
                'es-ES'
            ),
            'de-DE' => ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['de-DE']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['de-DE']->targetSiteId
                ),
                'de-DE'
            ),
            'ru-RU' => ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationsMapped[$element->getId()]['ru-RU']->translatedDraftId,
                    null,
                    $translationsMapped[$element->getId()]['ru-RU']->targetSiteId
                ),
                'ru-RU'
            ),
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

    private function getTranslationsResponseBody(
        int $elementId,
        string $status
    ): array {
        $fileName = sprintf('497058_element_%d_first-entry-user-1.json+html', $elementId);

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
}
