<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\I18NRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
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
    public function testSyncSuccess(IntegrationTester $I, $scenario): void
    {
        $scenario->skip('Content is not getting updated and missing in source content');

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
                    'name' => '497058_element_505_first-entry-user-1.json+html',
                    'status' => 'export_complete',
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                1 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703696,
                    'name' => '497058_element_505_first-entry-user-1.json+html',
                    'status' => 'export_complete',
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
                2 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703697,
                    'name' => '497058_element_505_first-entry-user-1.json+html',
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

        $expectedSpanishBody = $this->getTranslatedContent($translations, $element->getId(), 'es-ES', 'es-ES: ');
        $expectedGermanBody = $this->getTranslatedContent($translations, $element->getId(), 'de-DE', 'de-DE: ');
        $expectedRussianBody = $this->getTranslatedContent($translations, $element->getId(), 'ru-RU', 'ru-RU: ');

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            $expectedSpanishBody
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            $expectedGermanBody
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            $expectedRussianBody
        );

        $I->stopFollowingRedirects();
        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH,
                $job->id
            )
        );

        $I->assertTranslationsContentMatch(
            $translations,
            [
                /** I18N functionality doesn't present in content body  */
                'es-ES' => $this->getTranslatedContent($translations, $element->getId(), 'es-ES'),
                'de-DE' => $this->getTranslatedContent($translations, $element->getId(), 'de-DE'),
                'ru-RU' => $this->getTranslatedContent($translations, $element->getId(), 'ru-RU'),
            ]
        );

        $deSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE');
        $esSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES');
        $ruSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('ru-RU');

        $I->assertI18NRecordsExist($deSiteId, ExpectedElementContent::getExpectedI18N('de-DE: '));
        $I->assertI18NRecordsExist($esSiteId, ExpectedElementContent::getExpectedI18N('es-ES: '));
        $I->assertI18NRecordsExist($ruSiteId, ExpectedElementContent::getExpectedI18N('ru-RU: '));

        $i18nRecords = Craftliltplugin::getInstance()->i18NRepository->findAllByTargetSiteId($deSiteId);

        $expectedI18nRecords = $this->getExpectedI18nRecords();
        $actualI18nRecords = array_map(static function (I18NRecord $i18nRecord) {
            return $i18nRecord->toArray([
                'sourceSiteId',
                'targetSiteId',
                'source',
                'target',
                'hash'
            ]);
        }, $i18nRecords);

        Assert::assertEquals($expectedI18nRecords, $actualI18nRecords);
    }

    public function testSyncJobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->amOnPage(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH,
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
                CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH,
                123123
            )
        );

        $I->seeResponseCodeIs(404);
    }

    private function getTranslatedContent(array $translations, int $elementId, string $target, string $i18n = ''): array
    {
        /**
         * @var TranslationRecord[][]
         */
        $translationsMapped = [];
        foreach ($translations as $translation) {
            $translationsMapped[$translation->elementId][Craftliltplugin::getInstance(
            )->languageMapper->getLanguageBySiteId($translation->targetSiteId)] = $translation;
        }

        return ExpectedElementContent::getExpectedBody(
            Craft::$app->elements->getElementById(
                $translationsMapped[$elementId][$target]->translatedDraftId,
                null,
                $translationsMapped[$elementId][$target]->targetSiteId
            ),
            $target . ': ',
            $i18n
        );
    }

    /**
     * @return array[]
     */
    private function getExpectedI18nRecords(): array
    {
        $expectedI18nRecords = [
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'First checkbox label',
                'target' => 'de-DE: First checkbox label',
                'hash' => '1d93604319174b1f48d2ebb36acd37b8',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Second checkbox label',
                'target' => 'de-DE: Second checkbox label',
                'hash' => '9178d0b17d9cc2b5b5c98049fd98146a',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Third checkbox label',
                'target' => 'de-DE: Third checkbox label',
                'hash' => '63cbe6661a5878508d9e12e3e20afd02',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'The label text to display beside the lightswitch’s enabled state',
                'target' => 'de-DE: The label text to display beside the lightswitch’s enabled state',
                'hash' => '872f66ce13cdf0f6dbe2ca683240c17b',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'The label text to display beside the lightswitch’s disabled state.',
                'target' => 'de-DE: The label text to display beside the lightswitch’s disabled state.',
                'hash' => '136dd2cb9fa326349881737d14ad3e24',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Column Heading 1',
                'target' => 'de-DE: Column Heading 1',
                'hash' => '11fc84ec58b1be6a41ce61f9012643d2',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Column Heading 2',
                'target' => 'de-DE: Column Heading 2',
                'hash' => 'a15ec8913f1a5b54c6afd5c84cc52300',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Column Heading 3',
                'target' => 'de-DE: Column Heading 3',
                'hash' => 'ae9c3761b2a664d27c5baf845cb1fcfa',
            ],
            [
                'sourceSiteId' => 1,
                'targetSiteId' => 2,
                'source' => 'Column Heading 4',
                'target' => 'de-DE: Column Heading 4',
                'hash' => 'c2c1b7547dbc6b731519c5b2d773ea1e',
            ],
        ];
        return $expectedI18nRecords;
    }
}
