<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Craft;
use craft\elements\Entry;
use craft\errors\MissingComponentException;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\PostCreateJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class PostCreateJobControllerCest
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
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function testCreateJob(IntegrationTester $I): void
    {
        $element = Entry::findOne(['authorId' => 1]);

        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->sendAjaxPostRequest(
            '?p=admin/craft-lilt-plugin/job/create',
            [
                'csrf' => Craft::$app->getRequest()->getCsrfToken(true),
                'entries' => json_encode([$element->id]),
                'title' => 'Awesome test job',
                'sourceSite' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
                'targetSiteIds' => '*',
            ]
        );

        $job = Job::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        Assert::assertSame('Awesome test job', $job->title);
        Assert::assertSame([$element->id], $job->getElementIds());
        Assert::assertSame(SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT, $job->translationWorkflow);
        Assert::assertSame(
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            $job->sourceSiteId
        );
        Assert::assertSame(
            'en-US',
            $job->sourceSiteLanguage
        );

        Assert::assertCount(3, $job->getTargetSiteIds());

        Assert::assertEquals(
            ['de-DE', 'es-ES', 'ru-RU'],
            Craftliltplugin::getInstance()
                ->languageMapper
                ->getLanguagesBySiteIds(
                    $job->getTargetSiteIds()
                )
        );

        $I->seeResponseCodeIs(302);
        $I->seeHeader(
            'x-redirect',
            sprintf('https://localhost/index.php?p=admin/craft-lilt-plugin/job/edit/%d', $job->id)
        );
    }
}
