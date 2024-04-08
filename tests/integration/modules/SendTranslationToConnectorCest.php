<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\modules;

use Codeception\Exception\ModuleException;
use Codeception\Util\HttpCode;
use Craft;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use IntegrationTester;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use PHPUnit\Framework\Assert;

class SendTranslationToConnectorCest extends AbstractIntegrationCest
{
    public function _after(IntegrationTester $I): void
    {
        parent::_after($I);

        $I->disableOption(SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC);
    }

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ]
        ];
    }

    /**
     * @throws ModuleException
     * @throws InvalidFieldException
     */
    public function testSendTranslationSuccess(IntegrationTester $I): void
    {
        $I->clearQueue();

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 1000,
            'status' => Job::STATUS_IN_PROGRESS,
        ]);

        $translationsMapped = [];
        foreach ($translations as $translation) {
            $language = Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId($translation->targetSiteId);
            $translationsMapped[$language] = $translation;
        }


        // mark ru translation as sent
        $translationsMapped['ru-RU']->connectorTranslationId = 1111;
        $translationsMapped['ru-RU']->status = TranslationRecord::STATUS_IN_PROGRESS;
        $translationsMapped['ru-RU']->sourceContent = json_encode(["test" => "already translated"]);
        $translationsMapped['ru-RU']->save();

        // mark es translation as sent
        $translationsMapped['es-ES']->status = TranslationRecord::STATUS_IN_PROGRESS;
        $translationsMapped['es-ES']->sourceContent = json_encode(["test" => "already translated"]);
        $translationsMapped['es-ES']->save();

        $translationsMapped['de-DE']->status = TranslationRecord::STATUS_NEW;
        $translationsMapped['de-DE']->sourceContent = null;
        $translationsMapped['de-DE']->translatedDraftId = null;
        $translationsMapped['de-DE']->save();

        $I->expectJobGetRequest(
            1000,
            200,
            [
                'status' => JobResponse::STATUS_DRAFT
            ]
        );

        $expectedUrlDe = sprintf(
            '/api/v1.0/jobs/1000/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=de-DE'
            . '&due=',
            urlencode(
                sprintf('element_%d_first-entry-user-1.json+html', $elementToTranslate->getId())
            )
        );
        $I->expectJobTranslationsRequest($expectedUrlDe, [], HttpCode::OK);

        $I->expectJobStartRequest(1000, HttpCode::OK);

        $I->runQueue(
            SendTranslationToConnector::class,
            [
                'jobId' => $job->id,
                'translationId' => $translationsMapped['de-DE']->id,
                'targetSiteId' => $translationsMapped['de-DE']->targetSiteId,
                'versionId' => $translationsMapped['de-DE']->versionId,
                'elementId' => $translationsMapped['de-DE']->elementId,
            ]
        );

        $expectSourceContentForTranslationId = $translationsMapped['de-DE']->id;
        $translations = array_map(
            static function (
                TranslationRecord $translationRecord
            ) use (
                $elementToTranslate,
                $expectSourceContentForTranslationId
            ) {
                $element = Craft::$app->elements->getElementById(
                    $translationRecord->translatedDraftId,
                    null,
                    $translationRecord->targetSiteId
                );

                $expectedBody = ExpectedElementContent::getExpectedBody($element);

                Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
                Assert::assertSame($elementToTranslate->id, $translationRecord->versionId);
                Assert::assertSame($elementToTranslate->id, $translationRecord->elementId);

                if($expectSourceContentForTranslationId === $translationRecord->id) {
                    Assert::assertEquals($expectedBody, $translationRecord->sourceContent);
                }

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

        $jobActual = Job::findOne(['id' => $job->id]);
        Assert::assertSame(Job::STATUS_IN_PROGRESS, $jobActual->status);

        $I->assertJobInQueue(
            new FetchJobStatusFromConnector([
                'liltJobId' => 1000,
                'jobId' => $job->id
            ])
        );
    }

    /**
     * @throws ModuleException
     * @throws InvalidFieldException
     */
    public function testSendTranslationSuccessDisableAutomaticSync(IntegrationTester $I): void
    {
        $I->clearQueue();

        $I->enableOption(SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $elementToTranslate = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$elementToTranslate->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 1000,
            'status' => Job::STATUS_IN_PROGRESS,
        ]);

        $translationsMapped = [];
        foreach ($translations as $translation) {
            $language = Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId($translation->targetSiteId);
            $translationsMapped[$language] = $translation;
        }


        // mark ru translation as sent
        $translationsMapped['ru-RU']->connectorTranslationId = 1111;
        $translationsMapped['ru-RU']->status = TranslationRecord::STATUS_IN_PROGRESS;
        $translationsMapped['ru-RU']->sourceContent = json_encode(["test" => "already translated"]);
        $translationsMapped['ru-RU']->save();

        // mark es translation as sent
        $translationsMapped['es-ES']->status = TranslationRecord::STATUS_IN_PROGRESS;
        $translationsMapped['es-ES']->sourceContent = json_encode(["test" => "already translated"]);
        $translationsMapped['es-ES']->save();

        $translationsMapped['de-DE']->status = TranslationRecord::STATUS_NEW;
        $translationsMapped['de-DE']->sourceContent = null;
        $translationsMapped['de-DE']->translatedDraftId = null;
        $translationsMapped['de-DE']->save();

        $I->expectJobGetRequest(
            1000,
            200,
            [
                'status' => JobResponse::STATUS_DRAFT
            ]
        );

        $expectedUrlDe = sprintf(
            '/api/v1.0/jobs/1000/files?name=%s'
            . '&srclang=en-US'
            . '&trglang=de-DE'
            . '&due=',
            urlencode(
                sprintf('element_%d_first-entry-user-1.json+html', $elementToTranslate->getId())
            )
        );
        $I->expectJobTranslationsRequest($expectedUrlDe, [], HttpCode::OK);

        $I->expectJobStartRequest(1000, HttpCode::OK);

        $I->runQueue(
            SendTranslationToConnector::class,
            [
                'jobId' => $job->id,
                'translationId' => $translationsMapped['de-DE']->id,
                'targetSiteId' => $translationsMapped['de-DE']->targetSiteId,
                'versionId' => $translationsMapped['de-DE']->versionId,
                'elementId' => $translationsMapped['de-DE']->elementId,
            ]
        );

        $expectSourceContentForTranslationId = $translationsMapped['de-DE']->id;
        $translations = array_map(
            static function (
                TranslationRecord $translationRecord
            ) use (
                $elementToTranslate,
                $expectSourceContentForTranslationId
            ) {
                $element = Craft::$app->elements->getElementById(
                    $translationRecord->translatedDraftId,
                    null,
                    $translationRecord->targetSiteId
                );

                $expectedBody = ExpectedElementContent::getExpectedBody($element);

                Assert::assertSame(Job::STATUS_IN_PROGRESS, $translationRecord->status);
                Assert::assertSame($elementToTranslate->id, $translationRecord->versionId);
                Assert::assertSame($elementToTranslate->id, $translationRecord->elementId);

                if($expectSourceContentForTranslationId === $translationRecord->id) {
                    Assert::assertEquals($expectedBody, $translationRecord->sourceContent);
                }

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

        $jobActual = Job::findOne(['id' => $job->id]);
        Assert::assertSame(Job::STATUS_IN_PROGRESS, $jobActual->status);

        $totalJobs = Craft::$app->queue->getJobInfo();
        Assert::assertCount(0, $totalJobs);
    }
}
