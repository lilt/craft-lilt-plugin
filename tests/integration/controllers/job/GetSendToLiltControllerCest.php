<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use benf\neo\elements\Block;
use benf\neo\elements\db\BlockQuery;
use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\base\Element;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\craftliltplugintests\integration\stubs\ConnectorFileRepositoryStub;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
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
     * @throws InvalidConfigException
     */
    public function testCreateJob(IntegrationTester $I): void
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

        $expectQueueJob = new FetchJobStatusFromConnector([
            'liltJobId' => 1000,
            'jobId' => $job->id
        ]);

        $expectedUrl = sprintf(
            '/api/v1.0/jobs/1000/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=de-DE'
            . '&trglang=es-ES'
            . '&trglang=ru-RU' .
            '&due=',
            urlencode(
                sprintf('element_%d.json+html', $elementToTranslate->getId())
            )
        );

        $I->expectJobCreateRequest(
            [
                'project_prefix' => 'Awesome test job',
                'lilt_translation_workflow' => 'INSTANT',
            ],
            200,
            ['id' => 1000,]
        );

        $I->expectJobTranslationsRequest($expectedUrl, [], HttpCode::OK);

        $I->expectJobStartRequest(1000, HttpCode::OK);

        $I->stopFollowingRedirects();
        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH,
                $job->id
            )
        );

        $jobActual = Job::findOne(['id' => $job->id]);

        $translations = array_map(static function (TranslationRecord $translationRecord) use ($elementToTranslate) {
            $element = Craft::$app->elements->getElementById(
                $translationRecord->translatedDraftId,
                null,
                $translationRecord->targetSiteId
            );

            $expectedBody = ExpectedElementContent::getExpectedBody($element);

            Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
            Assert::assertSame($elementToTranslate->id, $translationRecord->versionId);
            Assert::assertSame($elementToTranslate->id, $translationRecord->elementId);
            Assert::assertEquals($expectedBody, $translationRecord->sourceContent);
            Assert::assertSame(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                $translationRecord->sourceSiteId
            );

            return [
                'versionId' => $translationRecord->versionId,
                'translatedDraftId' => $translationRecord->translatedDraftId,
                'sourceSiteId' => $translationRecord->sourceSiteId,
                'targetSiteId' => $translationRecord->targetSiteId,
                'sourceContent' => $translationRecord->sourceContent,
                'status' => $translationRecord->status,
                'connectorTranslationId' => $translationRecord->connectorTranslationId,
            ];
        }, TranslationRecord::findAll(['jobId' => $job->id, 'elementId' => $elementToTranslate->id]));

        $languages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            array_column($translations, 'targetSiteId')
        );
        sort($languages);

        Assert::assertEquals(
            ['de-DE', 'es-ES', 'ru-RU'],
            $languages
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

        $I->expectJobTranslationsRequest($expectedUrl, [], HttpCode::INTERNAL_SERVER_ERROR);

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

        $I->stopFollowingRedirects();
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

    public function testSyncJobNotFound(IntegrationTester $I): void
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

    public function testSyncWrongMethod(IntegrationTester $I): void
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

    /**
     * @throws InvalidFieldException
     */
    private function getExpectedBody(Entry $element, string $prefix = ''): array
    {
        $matrixContent = $this->getExpectedMatrixContent($element);
        $neoContent = $this->getExpectedNeoContent($element);
        $supertableContent = $this->getExpectedSupertableValue($element);

        return [
            $element->getId() => [
                'title' => $prefix . 'Some example title',
                'redactor' => $prefix . '<h1>Here is some header text</h1> Here is some content',
                'matrix' => $matrixContent,
                'checkboxes' => [
                    'firstCheckboxLabel' => $prefix . 'First checkbox label',
                    'secondCheckboxLabel' => $prefix . 'Second checkbox label',
                    'thirdCheckboxLabel' => $prefix . 'Third checkbox label',
                ],
                'lightswitch' => [
                    'onLabel' => $prefix . 'The label text to display beside the lightswitch’s enabled state',
                    'offLabel' => $prefix . 'The label text to display beside the lightswitch’s disabled state.',
                ],
                'supertable' => $supertableContent,
                'table' => [
                    'columns' => [
                        'columnHeading1' => $prefix . 'Column Heading 1',
                        'columnHeading2' => $prefix . 'Column Heading 2',
                        'columnHeading3' => $prefix . 'Column Heading 3',
                        'columnHeading4' => $prefix . 'Column Heading 4',
                    ],
                    'content' => [
                        0 => [
                            'columnHeading1' => $prefix . 'First row first value',
                            'columnHeading2' => $prefix . 'First row second value',
                            'columnHeading3' => $prefix . 'First row third value',
                            'columnHeading4' => $prefix . 'First row fourth value',
                        ],
                        1 => [
                            'columnHeading1' => $prefix . 'Second row first value',
                            'columnHeading2' => $prefix . 'Second row second value',
                            'columnHeading3' => $prefix . 'Second row third value',
                            'columnHeading4' => $prefix . 'Second row fourth value',
                        ],
                    ],
                ],
                'neo' => $neoContent,
            ],
        ];
    }

    private function getExpectedMatrixContent(Element $element, string $prefix = ''): array
    {
        /**
         * @var MatrixBlockQuery
         */
        $matrixFieldValue = $element->getFieldValue('matrix');

        $firstBlockId = $matrixFieldValue->type('firstBlock')->one()->id;
        $secondBlockId = $matrixFieldValue->type('secondblock')->one()->id;

        $content = [
            $firstBlockId => [
                'fields' => [
                    'plainTextFirstBlock' => sprintf('%s' . 'Plain text first block', $prefix)
                ]
            ],
            $secondBlockId => [
                'fields' => [
                    'plainTextSecondBlock' => sprintf('%s' . 'Plain text second block', $prefix)
                ]
            ],
        ];

        return $content;
    }

    private function getExpectedSupertableValue(Entry $element, string $prefix = ''): array
    {
        $content = [];
        $field = $element->getFieldValue('supertable');
        $blocks = $field->all();

        foreach ($blocks as $block) {
            $content[$block->id] = [
                'fields' => [
                    'firstField' => $prefix . 'firstField - Supertable text',
                    'secondField' => $prefix . 'secondField - Supertable text',
                ],
            ];
        }

        return $content;
    }

    private function getExpectedNeoContent(Entry $element, string $prefix = ''): array
    {
        /**
         * @var BlockQuery
         */
        $neoFieldValue = $element->getFieldValue('neo');

        /**
         * @var Block $firstBlock
         */
        $firstBlock = $neoFieldValue->type('firstBlockType')->one();
        $firstBlockId = $neoFieldValue->type('firstBlockType')->one()->id;
        $secondBlockId = $neoFieldValue->type('secondBlockType')->one()->id;

        return [
            $firstBlockId => [
                'fields' => [
                    'redactor' => $prefix . 'firstBlockType - redactor - Here is value of field',
                    'lightswitch' => [
                        'onLabel' => $prefix . 'The label text to display beside the lightswitch’s enabled state',
                        'offLabel' => $prefix . 'The label text to display beside the lightswitch’s disabled state.',
                    ],
                    'matrix' => $this->getExpectedMatrixContent($firstBlock, 'neo - firstBlockType - matrix - '),
                ],
            ],
            $secondBlockId => [
                'fields' => [
                    'plainText' => $prefix . 'secondBlockType - plainText - Here is value of field',
                    'table' => [
                        'content' => [
                            0 => [
                                'columnHeading1' => $prefix . 'secondBlockType - table - First row first value',
                                'columnHeading2' => $prefix . 'secondBlockType - table - First row second value',
                                'columnHeading3' => $prefix . 'secondBlockType - table - First row third value',
                                'columnHeading4' => $prefix . 'secondBlockType - table - First row fourth value',
                            ],
                            1 => [
                                'columnHeading1' => $prefix . 'secondBlockType - table - Second row first value',
                                'columnHeading2' => $prefix . 'secondBlockType - table - Second row second value',
                                'columnHeading3' => $prefix . 'secondBlockType - table - Second row third value',
                                'columnHeading4' => $prefix . 'secondBlockType - table - Second row fourth value',
                            ],
                        ],
                    ],
                    'supertable' => [
                    ],
                ],
            ],
        ];
    }
}
