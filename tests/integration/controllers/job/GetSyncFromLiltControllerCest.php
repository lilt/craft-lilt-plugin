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
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
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
     *
     * @skip TODO: We can't assert body since we don't know draft id. We need the way to know draft id!
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

        $I->expectTranslationDownloadRequest(
            703695,
            HttpCode::OK,
            $this->getExpectedContentEs($element, 'es-ES: ')
        );

        $I->expectTranslationDownloadRequest(
            703696,
            HttpCode::OK,
            $this->getExpectedContentDe($element, 'de-DE: ')
        );

        $I->expectTranslationDownloadRequest(
            703697,
            HttpCode::OK,
            $this->getExpectedContentRu($element, 'ru-RU: ')
        );

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
                'es-ES' => $this->getExpectedContentEs($element),
                'de-DE' => $this->getExpectedContentDe($element),
                'ru-RU' => $this->getExpectedContentRu($element),
            ]
        );

        $deSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE');
        $esSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES');
        $ruSiteId = Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('ru-RU');

        $I->assertI18NRecordsExist($deSiteId, ExpectedElementContent::getExpectedI18N('de-DE: '));
        $I->assertI18NRecordsExist($esSiteId, ExpectedElementContent::getExpectedI18N('es-ES: '));
        $I->assertI18NRecordsExist($ruSiteId, ExpectedElementContent::getExpectedI18N('ru-RU: '));
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

    private function getExpectedContentDe(Entry $element, string $i18nPrefix = ''): array
    {
        return ExpectedElementContent::getExpectedBody($element, 'de-DE: ', $i18nPrefix);
    }

    private function getExpectedContentEs(Entry $element, string $i18nPrefix = ''): array
    {
        return ExpectedElementContent::getExpectedBody($element, 'es-ES: ', $i18nPrefix);
    }

    private function getExpectedContentRu(Entry $element, string $i18nPrefix = ''): array
    {
        return ExpectedElementContent::getExpectedBody($element, 'ru-RU: ', $i18nPrefix);
    }
}
