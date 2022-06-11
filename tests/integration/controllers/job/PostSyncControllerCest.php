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
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use yii\base\InvalidConfigException;

class PostSyncControllerCest extends AbstractIntegrationCest
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
     * @throws \craft\errors\InvalidFieldException
     * @throws ModuleException
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
        [$job777, $translations777] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        [$job888, $translations888] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 888,
        ]);

        $I->expectJobGetRequest(
            777,
            200,
            [
                'status' => JobResponse::STATUS_COMPLETE
            ]
        );

        $I->expectJobGetRequest(
            888,
            200,
            [
                'status' => JobResponse::STATUS_COMPLETE
            ]
        );

        $responseBody777 = [
            'limit' => 25,
            'start' => 0,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703695,
                    'name' => '497058_element_505.json+html',
                    'status' => TranslationResponse::STATUS_IMPORT_COMPLETE,
                    'trgLang' => 'es',
                    'trgLocale' => 'ES',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ]
        ];
        $I->expectTranslationsGetRequest(
            777,
            0,
            100,
            HttpCode::OK,
            $responseBody777
        );

        $responseBody888 = [
            'limit' => 25,
            'start' => 0,
            'results' => [
                0 => [
                    'createdAt' => '2022-05-29T11:31:58',
                    'errorMsg' => null,
                    'id' => 703696,
                    'name' => '497058_element_505.json+html',
                    'status' => TranslationResponse::STATUS_IMPORT_COMPLETE,
                    'trgLang' => 'de',
                    'trgLocale' => 'DE',
                    'updatedAt' => '2022-06-02T23:01:42',
                ],
            ],
        ];

        $I->expectTranslationsGetRequest(
            888,
            0,
            100,
            HttpCode::OK,
            $responseBody888
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_PATH,
            ),
            [
                'jobIds' => [$job777->id, $job888->id]
            ]
        );

        $I->seeResponseCodeIs(200);

        $I->assertJobStatus($job777->id, Job::STATUS_IN_PROGRESS);
        $I->assertJobStatus($job888->id, Job::STATUS_IN_PROGRESS);

        $I->assertTranslationStatus($translations777[0]->id, Job::STATUS_IN_PROGRESS);
        $I->assertTranslationStatus($translations888[0]->id, Job::STATUS_IN_PROGRESS);
    }

    public function testSyncJobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_PATH
            ),
            [
                123,
                456,
                789
            ]
        );

        $I->seeResponseCodeIs(400);
    }

    public function testSyncWrongMethod(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxGetRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::JOB_POST_SYNC_ACTION
            )
        );

        $I->seeResponseCodeIs(404);
    }
}
