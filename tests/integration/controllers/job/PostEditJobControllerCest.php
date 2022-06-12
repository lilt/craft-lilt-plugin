<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Exception\ModuleException;
use Craft;
use craft\elements\Entry;
use craft\errors\MissingComponentException;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class PostEditJobControllerCest extends AbstractIntegrationCest
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
        return Craft::$app->createController('craft-lilt-plugin/job/post-edit-job/invoke')[0];
    }

    /**
     * @throws MissingComponentException
     * @throws InvalidConfigException
     * @throws Exception|ModuleException
     * @throws \JsonException
     */
    public function testCreateJob(IntegrationTester $I): void
    {
        $element = Entry::findOne(['authorId' => 1]);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        /**
         * @var Job $job
         * @var TranslationRecord[] $translations
         */
        [$job, $translations] = $I->createJobWithTranslations([
            'title' => 'Awesome test job',
            'elementIds' => [$element->id],
            'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES')],
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            'versions' => [],
            'authorId' => 1,
        ]);

        $I->sendAjaxPostRequest(
            sprintf('?p=admin/craft-lilt-plugin/job/edit/%d', $job->id),
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'jobId' => $job->id,
                'entries' => json_encode(['1', '2', '3', '4'], JSON_THROW_ON_ERROR),
                'title' => 'Edited: Awesome test job',
                'sourceSite' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE'),
                'targetSiteIds' => [Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US')],
                'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            ]
        );
        $I->seeResponseCodeIs(302);

        $job = Job::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $I->seeHeader(
            'x-redirect',
            sprintf('https://localhost/index.php?p=admin/craft-lilt-plugin/job/edit/%d', $job->id)
        );

        Assert::assertSame('Edited: Awesome test job', $job->title);
        Assert::assertSame([1, 2, 3, 4], $job->getElementIds());
        Assert::assertSame(SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT, $job->translationWorkflow);
        Assert::assertSame(
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE'),
            $job->sourceSiteId
        );
        Assert::assertSame(
            'de-DE',
            $job->sourceSiteLanguage
        );

        Assert::assertCount(1, $job->getTargetSiteIds());

        Assert::assertEquals(
            ['en-US'],
            Craftliltplugin::getInstance()
                ->languageMapper
                ->getLanguagesBySiteIds(
                    $job->getTargetSiteIds()
                )
        );
    }
}
