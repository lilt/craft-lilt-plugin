<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class PostJobRetryControllerCest extends AbstractIntegrationCest
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
     * @throws InvalidFieldException
     * @throws ModuleException
     */
    public function testRetrySuccess(IntegrationTester $I, $scenario): void
    {
        $I->setEnableSplitJobFileUpload(0);

        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        $jobRecord->status = Job::STATUS_FAILED;
        $jobRecord->save();

        $expectQueueJob = new FetchJobStatusFromConnector([
            'liltJobId' => 777,
            'jobId' => $job->id
        ]);

        $I->expectJobCreateRequest(
            [
                'project_prefix' => 'Awesome test job',
                'lilt_translation_workflow' => 'VERIFIED',
            ],
            200,
            ['id' => 777,]
        );

        $expectedUrl = sprintf(
            '/api/v1.0/jobs/777/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=es-ES' .
            '&due=',
            urlencode(
                sprintf('element_%d_first-entry-user-1.json+html', $element->getId())
            )
        );
        $expectedBody = ExpectedElementContent::getExpectedBody($element);

        $I->expectJobTranslationsRequest($expectedUrl, $expectedBody, HttpCode::OK);
        $I->expectJobStartRequest(777, HttpCode::OK);

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_RETRY_PATH
            ),
            ['jobIds' => [$job->id]]
        );

        $translations = array_map(static function (TranslationRecord $translationRecord) use ($element) {
            $expectedDraftBody = ExpectedElementContent::getExpectedBody(
                Craft::$app->elements->getElementById(
                    $translationRecord->translatedDraftId,
                    null,
                    $translationRecord->targetSiteId
                )
            );

            Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
            Assert::assertSame($element->id, $translationRecord->versionId);
            Assert::assertEquals($expectedDraftBody, $translationRecord->sourceContent);
            Assert::assertSame(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                $translationRecord->sourceSiteId
            );
            Assert::assertNotNull($translationRecord->translatedDraftId);

            return [
                'versionId' => $translationRecord->versionId,
                'translatedDraftId' => $translationRecord->translatedDraftId,
                'sourceSiteId' => $translationRecord->sourceSiteId,
                'targetSiteId' => $translationRecord->targetSiteId,
                'sourceContent' => $translationRecord->sourceContent,
                'status' => $translationRecord->status,
                'connectorTranslationId' => $translationRecord->connectorTranslationId,
            ];
        }, TranslationRecord::findAll(['jobId' => $job->id, 'elementId' => $element->id]));

        Assert::assertCount(1, $translations);
        Assert::assertEquals(
            ['es-ES'],
            Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
                array_column($translations, 'targetSiteId')
            )
        );

        $I->assertJobInQueue($expectQueueJob);
        $I->seeResponseCodeIs(200);
        $I->assertJobStatus($job->id, Job::STATUS_IN_PROGRESS);
    }

    public function testRetryJobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_RETRY_PATH
            ),
            ['jobIds' => [123]]
        );

        $I->seeResponseCodeIs(404);
    }

    public function testRetryBadRequest(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_RETRY_PATH
            ),
            []
        );

        $I->seeResponseCodeIs(400);
    }

    public function testRetryWrongMethod(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxGetRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_RETRY_PATH
            )
        );

        $I->seeResponseCodeIs(404);
    }
}
