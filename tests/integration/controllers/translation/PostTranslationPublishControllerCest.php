<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\translation;

use Codeception\Exception\ModuleException;
use Craft;
use craft\elements\Entry;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\appliers\TranslationApplyCommand;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;

class PostTranslationPublishControllerCest extends AbstractIntegrationCest
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
     * @throws ModuleException
     */
    public function testPublishTranslation(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $siteIds = Craftliltplugin::getInstance()->languageMapper->getSiteIdsByLanguages(['ru-RU', 'de-DE', 'es-ES']);

        $element = Entry::findOne(['authorId' => 1]);

        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => $siteIds,
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);


        $draft = Craftliltplugin::getInstance()->elementTranslatableContentApplier->createDraftElement(
            new TranslationApplyCommand($element, $job, [], 'ru-RU'),
            []
        );
        $draft->title = 'This is draft, and it was applied from PostTranslationPublishControllerCest';
        Craft::$app->elements->saveElement($draft);

        $translationToSubmit = $I->setTranslationStatus(
            $job->id,
            $element->id,
            'ru-RU',
            TranslationRecord::STATUS_READY_FOR_REVIEW
        );
        $translationToSubmit->translatedDraftId = $draft->id;
        $translationToSubmit->save();

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'de-DE',
            TranslationRecord::STATUS_PUBLISHED
        );

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'es-ES',
            TranslationRecord::STATUS_PUBLISHED
        );

        $I->sendAjaxPostRequest(
            sprintf('?p=admin/actions/%s', CraftliltpluginParameters::TRANSLATION_PUBLISH_ACTION),
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'translationId' => $translationToSubmit->id,
            ]
        );

        $I->seeResponseCodeIs(200);

        Craft::$app->elements->invalidateCachesForElement($element);
        $actualElement = Craft::$app->elements->getElementById(
            $element->id,
            'craft\elements\Entry',
            $translationToSubmit->targetSiteId
        );

        Assert::assertSame(
            'This is draft, and it was applied from PostTranslationPublishControllerCest',
            $actualElement->title
        );

        $I->assertTranslationStatus($translationToSubmit->id, TranslationRecord::STATUS_PUBLISHED);
        $I->assertJobStatus($job->id, Job::STATUS_COMPLETE);
    }

    public function testPublishTranslationJobStatusStaysSame(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $siteIds = Craftliltplugin::getInstance()->languageMapper->getSiteIdsByLanguages(['ru-RU', 'de-DE', 'es-ES']);

        $element = Entry::findOne(['authorId' => 1]);

        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => $siteIds,
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        $jobRecord->status = Job::STATUS_READY_TO_PUBLISH;
        $jobRecord->save();

        $draft = Craftliltplugin::getInstance()->elementTranslatableContentApplier->createDraftElement(
            new TranslationApplyCommand($element, $job, [], 'ru-RU'),
            []
        );
        $draft->title = 'This is draft, and it was applied from PostTranslationPublishControllerCest';
        Craft::$app->elements->saveElement($draft);

        $translationToSubmit = $I->setTranslationStatus(
            $job->id,
            $element->id,
            'ru-RU',
            TranslationRecord::STATUS_READY_FOR_REVIEW
        );
        $translationToSubmit->translatedDraftId = $draft->id;
        $translationToSubmit->save();

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'de-DE',
            TranslationRecord::STATUS_READY_FOR_REVIEW
        );

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'es-ES',
            TranslationRecord::STATUS_PUBLISHED
        );

        $I->sendAjaxPostRequest(
            sprintf('?p=admin/actions/%s', CraftliltpluginParameters::TRANSLATION_PUBLISH_ACTION),
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'translationId' => $translationToSubmit->id,
            ]
        );

        $I->seeResponseCodeIs(200);

        Craft::$app->elements->invalidateCachesForElement($element);
        $actualElement = Craft::$app->elements->getElementById(
            $element->id,
            'craft\elements\Entry',
            $translationToSubmit->targetSiteId
        );

        Assert::assertSame(
            'This is draft, and it was applied from PostTranslationPublishControllerCest',
            $actualElement->title
        );

        $I->assertTranslationStatus($translationToSubmit->id, TranslationRecord::STATUS_PUBLISHED);
        $I->assertJobStatus($job->id, Job::STATUS_READY_TO_PUBLISH);
    }

    // TODO: do we need a test case when all fields are translated?
}
