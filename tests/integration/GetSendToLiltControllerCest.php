<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\errors\MissingComponentException;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\job\CreateJobCommand;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use WireMock\Client\WireMock;
use yii\base\InvalidConfigException;

use function PHPUnit\Framework\assertSame;

class GetSendToLiltControllerCest
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
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function testCreateJob(IntegrationTester $I): void
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


        $wireMock = WireMock::create('wiremock', 80);
        Assert::assertTrue($wireMock->isAlive());

        $wireMock->stubFor(
            WireMock::post(
                WireMock::urlEqualTo(
                    sprintf(
                        '/api/v1.0/jobs/1000/files?name=%s'
                        . '&srclang=en-US'
                        . '&trglang=de-DE'
                        . '&trglang=es-ES'
                        . '&trglang=ru-RU' .
                        '&due=',
                        urlencode(
                            sprintf('element_%d.json+html', $element->getId())
                        )
                    )
                )
            )
                //{"project_prefix":"Awesome test job","lilt_translation_workflow":"INSTANT"}
                ->withRequestBody(
                    WireMock::equalToJson(
                        json_encode($body),
                        true,
                        false
                    )
                )
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                )
        );

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $job = $this->createJob([
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

        $translations = array_map(function (TranslationRecord $translationRecord) use ($element, $body) {
            Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
            Assert::assertSame($element->id, $translationRecord->versionId);
            Assert::assertEquals($body, $translationRecord->sourceContent);
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
    }

    private function createJob(array $data = []): Job
    {
        if ($data['targetSiteIds'] === '*') {
            $data['targetSiteIds'] = Craftliltplugin::getInstance()->languageMapper->getLanguageToSiteId();
        }

        $createJobCommand = new CreateJobCommand(
            $data['title'],
            $data['elementIds'],
            $data['targetSiteIds'],
            $data['sourceSiteId'],
            $data['translationWorkflow'],
            $data['versions'],
            $data['authorId']
        );

        return Craftliltplugin::getInstance()->createJobHandler->__invoke(
            $createJobCommand
        );
    }
}
