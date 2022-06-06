<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Codeception\Exception\ModuleException;
use Codeception\TestInterface;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

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

    /**
     * @throws InvalidConfigException
     */
    private function getController(): PostCreateJobController
    {
        Craftliltplugin::getInstance()->controllerNamespace = 'lilthq\craftliltplugin\controllers';
        return Craft::$app->createController('craft-lilt-plugin/job/post-create-job/invoke')[0];
    }

    /**
     * @throws ModuleException
     */
    public function testCreateJob(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
        ]);

        $expectQueueJob = new FetchJobStatusFromConnector([
            'liltJobId' => 1000,
            'jobId'     => $job->id
        ]);

        $expectedUrl = sprintf(
            '/api/v1.0/jobs/1000/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=de-DE'
            . '&trglang=es-ES'
            . '&trglang=ru-RU' .
            '&due=',
            urlencode(
                sprintf('element_%d.json+html', $element->getId())
            )
        );
        $expectedBody = $this->getExpectedBody($element);

        $I->expectJobCreateRequest(
            [
                'project_prefix' => 'Awesome test job',
                'lilt_translation_workflow' => 'INSTANT',
            ],
            200,
            ['id' => 1000,]
        );
        $I->expectJobTranslationsRequest($expectedUrl, $expectedBody, HttpCode::OK);
        $I->expectJobStartRequest(1000, HttpCode::OK);

        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                $job->id
            )
        );

        $jobActual = Job::findOne(['id' => $job->id]);
        $translations = array_map(function (TranslationRecord $translationRecord) use ($element, $expectedBody) {
            Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
            Assert::assertSame($element->id, $translationRecord->versionId);
            Assert::assertEquals($expectedBody, $translationRecord->sourceContent);
            Assert::assertSame(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                $translationRecord->sourceSiteId
            );
            Assert::assertNull($translationRecord->translatedDraftId);

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

        Assert::assertEquals(
            ['de-DE', 'es-ES', 'ru-RU'],
            Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
                array_column($translations, 'targetSiteId')
            )
        );

        Assert::assertSame(Job::STATUS_IN_PROGRESS, $jobActual->status);

        $I->assertJobInQueue($expectQueueJob);
    }

    /**
     * @throws ModuleException
     */
    public function testCreateJobWithUnexpectedStatusFromConnector(IntegrationTester $I): void
    {
        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $I->expectJobCreateRequest(
            [
                'project_prefix' => 'Awesome test job',
                'lilt_translation_workflow' => 'INSTANT',
            ],
            200,
            ['id' => 1000,]
        );

        $expectedUrl = sprintf(
            '/api/v1.0/jobs/1000/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=de-DE'
            . '&trglang=es-ES'
            . '&trglang=ru-RU' .
            '&due=',
            urlencode(
                sprintf('element_%d.json+html', $element->getId())
            )
        );
        $expectedBody = $this->getExpectedBody($element);

        $I->expectJobTranslationsRequest($expectedUrl, $expectedBody, HttpCode::INTERNAL_SERVER_ERROR);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
        ]);

        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                $job->id
            )
        );

        $jobActual = Job::findOne(['id' => $job->id]);

        Assert::assertEmpty(
            TranslationRecord::findAll(['jobId' => $job->id, 'elementId' => $element->id])
        );

        Assert::assertSame(Job::STATUS_FAILED, $jobActual->status);
    }

    /**
     * @throws InvalidFieldException
     */
    private function getExpectedBody(Entry $element): array
    {
        /**
         * @var MatrixBlockQuery $matrixField
         */
        $matrixField = $element->getFieldValue('matrixField');
        /**
         * @var MatrixBlock[] $blockElements
         */
        $blocks = $matrixField->all();
        $blocksMap = [];
        foreach ($blocks as $block) {
            $blocksMap[$block->type->handle] = $block->id;
        }

        $body = [
            $element->getId() => [
                'title' => 'Some example title',
                'body' => '<h1>Here is some header text</h1> Here is some content',
                'matrixField' => [
                    $blocksMap['firstBlock'] => [
                        'fields' => [
                            'plainTextFirstBlock' => 'Some text',
                        ],
                    ],
                    $blocksMap['secondblock'] => [
                        'fields' => [
                            'plainTextSecondBlock' => 'Some text',
                        ],
                    ],
                ],
            ],
        ];
        return $body;
    }
}
