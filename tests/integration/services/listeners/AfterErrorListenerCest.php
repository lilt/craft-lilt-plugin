<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\services\listeners;

use Codeception\Exception\ModuleException;
use craft\elements\Entry;
use Exception;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\listeners\AfterErrorListener;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use yii\queue\ExecEvent;
use IntegrationTester;

class AfterErrorListenerCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ],
        ];
    }

    /**
     * @throws ModuleException
     */
    public function testInvoke(IntegrationTester $I): void {
        $user = \Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::findOne(['authorId' => 1]);

        /**
         * @var Job $job
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

        $afterErrorListener = new AfterErrorListener();
        $event = new ExecEvent();

        $event->id = 123;
        $event->retry = false;
        $event->error = new Exception("Exception example");

        $event->job = new FetchJobStatusFromConnector([
            'jobId' => $job->id,
        ]);

        $afterErrorListener->__invoke($event);

        $I->assertJobStatus($job->id, Job::STATUS_FAILED);

        foreach ($translations as $translation) {
            $I->assertTranslationStatus($translation->id, Job::STATUS_FAILED);
        }
    }
}
