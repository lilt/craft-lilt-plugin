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
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;

class PostTranslationReviewControllerCest extends AbstractIntegrationCest
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
    public function testSubmitTranslation(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $siteIds = Craftliltplugin::getInstance()->languageMapper->getSiteIdsByLanguages(['ru-RU', 'de-DE', 'es-ES']);

        $element = Entry::findOne(['authorId' => 1]);

        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [ $element->id ],
            'targetSiteIds' => $siteIds,
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $translationToSubmit = $I->setTranslationStatus(
            $job->id,
            $element->id,
            'ru-RU',
            TranslationRecord::STATUS_READY_FOR_REVIEW
        );

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'de-DE',
            TranslationRecord::STATUS_READY_TO_PUBLISH
        );

        $I->setTranslationStatus(
            $job->id,
            $element->id,
            'es-ES',
            TranslationRecord::STATUS_READY_TO_PUBLISH
        );

        $I->sendAjaxPostRequest(
            sprintf('?p=admin/actions/%s', CraftliltpluginParameters::TRANSLATION_REVIEW_ACTION),
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'translationId' => $translationToSubmit->id,
            ]
        );

        $I->seeResponseCodeIs(200);

        $I->assertTranslationStatus($translationToSubmit->id, TranslationRecord::STATUS_READY_TO_PUBLISH);
        $I->assertJobStatus($job->id, Job::STATUS_READY_TO_PUBLISH);
    }

    /**
     * @throws ModuleException
     */
    public function testSubmitTranslationJobStatusStaysSame(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $siteIds = Craftliltplugin::getInstance()->languageMapper->getSiteIdsByLanguages(['ru-RU', 'de-DE', 'es-ES']);

        $element = Entry::findOne(['authorId' => 1]);

        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [ $element->id ],
            'targetSiteIds' => $siteIds,
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
            'liltJobId' => 777,
        ]);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        $jobRecord->status = Job::STATUS_READY_FOR_REVIEW;
        $jobRecord->save();

        $translationToSubmit = $I->setTranslationStatus(
            $job->id,
            $element->id,
            'ru-RU',
            TranslationRecord::STATUS_READY_FOR_REVIEW
        );

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
            TranslationRecord::STATUS_READY_TO_PUBLISH
        );

        $I->sendAjaxPostRequest(
            sprintf('?p=admin/actions/%s', CraftliltpluginParameters::TRANSLATION_REVIEW_ACTION),
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'translationId' => $translationToSubmit->id,
            ]
        );

        $I->seeResponseCodeIs(200);

        $I->assertTranslationStatus($translationToSubmit->id, TranslationRecord::STATUS_READY_TO_PUBLISH);
        $I->assertJobStatus($job->id, Job::STATUS_READY_FOR_REVIEW);
    }
}
