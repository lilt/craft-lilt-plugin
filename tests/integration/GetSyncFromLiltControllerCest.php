<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\errors\MissingComponentException;
use craft\services\Elements;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\job\CreateJobCommand;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class GetSyncFromLiltControllerCest extends AbstractIntegrationCest
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
        return Craft::$app->createController('craft-lilt-plugin/job/get-sync-from-lilt/invoke')[0];
    }

    /**
     * @throws \craft\errors\InvalidFieldException
     * @throws ModuleException
     */
    public function testCreateJob(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
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
                    'id' => 703695,
                    'name' => '497058_element_505.json+html',
                    'status' => 'export_complete',
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703696,
                    'name' => '497058_element_505.json+html',
                    'status' => 'export_complete',
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703697,
                    'name' => '497058_element_505.json+html',
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

        $expectedContent = $this->getExpectedContent($element);

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            $expectedContent['es-ES']
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            $expectedContent['de-DE']
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            $expectedContent['ru-RU']
        );

        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH,
                $job->id
            )
        );

        $this->assertTranslations($translations, $expectedContent);
    }

    private function getExpectedContent(Entry $element): array
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

        return [
            'de-DE' => [
                $element->getId() => [
                    'title' => 'de-DE: Some example title',
                    'body' => 'de-DE: <h1>Here is some header text</h1> Here is some content',
                    'matrixField' => [
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
                    'body' => 'es-ES: <h1>Here is some header text</h1> Here is some content',
                    'matrixField' => [
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
                    'body' => 'ru-RU: <h1>Here is some header text</h1> Here is some content',
                    'matrixField' => [
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

    /**
     * @param array $translations
     * @param array $expectedContent
     * @return void
     * @throws \craft\errors\InvalidFieldException
     */
    private function assertTranslations(array $translations, array $expectedContent): void
    {
        foreach ($translations as $translation) {
            $translation->refresh();

            Assert::assertNotEmpty($translation->translatedDraftId);

            $translatedDraft = Craft::$app->elements->getElementById(
                $translation->translatedDraftId,
                'craft\elements\Entry',
                $translation->targetSiteId
            );

            $appliedContent = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $translatedDraft
            );
            $translationTargetLanguage = Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                $translation->targetSiteId
            );

            //TODO: maybe we can write our own assertion to be sure that ids are correct
            //we definitely can't ignore keys
            Assert::assertEqualsCanonicalizing(
                $expectedContent[$translationTargetLanguage],
                $appliedContent
            );
        }
    }
}
