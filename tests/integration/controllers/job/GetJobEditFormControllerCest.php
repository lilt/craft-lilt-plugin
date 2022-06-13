<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Exception\ModuleException;
use Craft;
use craft\elements\Entry;
use JsonException;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\GetJobCreateFormController;
use lilthq\craftliltplugin\controllers\job\GetJobEditFormController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugintests\integration\ViewWrapper;
use lilthq\tests\fixtures\EntriesFixture;
use IntegrationTester;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class GetJobEditFormControllerCest
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
    private function getController(): GetJobEditFormController
    {
        Craftliltplugin::getInstance()->controllerNamespace = 'lilthq\craftliltplugin\controllers';
        return Craft::$app->createController('craft-lilt-plugin/job/get-job-edit-form/invoke')[0];
    }

    public function testSyncBadRequest(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxGetRequest(
            sprintf(
                '?p=admin/%s/0',
                CraftliltpluginParameters::JOB_EDIT_PATH
            )
        );

        $I->seeResponseCodeIs(400);
    }
    public function testSyncJobNotFound(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->sendAjaxGetRequest(
            sprintf(
                '?p=admin/%s/%d',
                CraftliltpluginParameters::JOB_EDIT_PATH,
                123
            )
        );

        $I->seeResponseCodeIs(404);
    }

    /**
     * @throws ModuleException
     * @throws InvalidConfigException
     * @throws JsonException
     */
    public function testSuccess(IntegrationTester $I): void {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $job = $I->createJob([
            'title' => 'Awesome test job',
            'elementIds' => [(string)$element->id], //string to check type conversion
            'targetSiteIds' => '*',
            'sourceSiteId' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
            'translationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            'versions' => [],
            'authorId' => 1,
        ]);

        $controller = $this->getController();
        $controller->setView(new ViewWrapper());

        $response = $controller->actionInvoke((string) $job->id);

        $expected = $this->getExpected($job);

        $actual = json_decode($response->data, true, 512, 4194304);

        Assert::assertEquals($expected['template'], $actual['template']);
        Assert::assertEquals(
            $expected['variables']['defaultTranslationWorkflow'],
            $actual['variables']['defaultTranslationWorkflow']
        );
        Assert::assertEquals(
            $expected['variables']['translationWorkflowsOptions'],
            $actual['variables']['translationWorkflowsOptions']
        );
        Assert::assertEquals($expected['variables']['availableSites'], $actual['variables']['availableSites']);
        Assert::assertEquals($expected['variables']['targetSites'], $actual['variables']['targetSites']);
        Assert::assertEquals(
            $expected['variables']['showLiltTranslateButton'],
            $actual['variables']['showLiltTranslateButton']
        );
        Assert::assertEquals($expected['variables']['isUnpublishedDraft'], $actual['variables']['isUnpublishedDraft']);
        Assert::assertEquals($expected['variables']['permissionSuffix'], $actual['variables']['permissionSuffix']);
        Assert::assertEquals(
            $expected['variables']['authorOptionCriteria'],
            $actual['variables']['authorOptionCriteria']
        );
        Assert::assertEquals($expected['variables']['crumbs'], $actual['variables']['crumbs']);

        foreach ($expected['variables']['element'] as $expectedKey => $expectedValue) {
            Assert::assertArrayHasKey($expectedKey, $actual['variables']['element']);
            Assert::assertSame($expectedValue, $actual['variables']['element'][$expectedKey]);
        }

        Assert::assertEquals($expected['templateMode'], $actual['templateMode']);
    }

    /**
     * @return array
     */
    private function getExpected(Job $job): array
    {
        $expected = [
            'template' => 'craft-lilt-plugin/job/edit.twig',
            'variables' => [
                'defaultTranslationWorkflow' => 'instant',
                'translationWorkflowsOptions' => [
                    'instant' => 'Instant',
                    'verified' => 'Verified',
                ],
                'availableSites' => [
                    0 => [
                        'value' => 4,
                        'label' => 'Craft test(en-US)',
                    ],
                    1 => [
                        'value' => 1,
                        'label' => 'Craft test de(de-DE)',
                    ],
                    2 => [
                        'value' => 3,
                        'label' => 'Craft test ru(ru-RU)',
                    ],
                    3 => [
                        'value' => 2,
                        'label' => 'Craft test es(es-ES)',
                    ],
                ],
                'targetSites' => [
                    4 => 'en-US',
                    1 => 'de-DE',
                    3 => 'ru-RU',
                    2 => 'es-ES',
                ],
                'element' => [
                    'authorId' => 1,
                    'title' => 'Awesome test job',
                    'liltJobId' => NULL,
                    'status' => 'new',
                    'sourceSiteId' => 4,
                    'sourceSiteLanguage' => 'en-US',
                    'targetSiteIds' => '{"de-DE": 1, "es-ES": 2, "ru-RU": 3}',
                    'elementIds' => sprintf('["%d"]', $job->getElementIds()[0]),
                    'versions' => '[]',
                    'dueDate' => NULL,
                    'translationWorkflow' => 'INSTANT',
                    'tempId' => NULL,
                    'draftId' => NULL,
                    'revisionId' => NULL,
                    'isProvisionalDraft' => false,
                    'fieldLayoutId' => NULL,
                    'structureId' => NULL,
                    'contentId' => NULL,
                    'enabled' => true,
                    'archived' => false,
                    'siteId' => 4,
                    'slug' => NULL,
                    'uri' => NULL,
                    'dateLastMerged' => NULL,
                    'dateDeleted' => NULL,
                    'root' => NULL,
                    'lft' => NULL,
                    'rgt' => NULL,
                    'level' => NULL,
                    'searchScore' => NULL,
                    'trashed' => false,
                    'awaitingFieldValues' => false,
                    'propagating' => false,
                    'propagateAll' => false,
                    'newSiteIds' => [
                    ],
                    'isNewForSite' => false,
                    'resaving' => false,
                    'duplicateOf' => NULL,
                    'firstSave' => false,
                    'mergingCanonicalChanges' => false,
                    'updatingFromDerivative' => false,
                    'previewing' => false,
                    'hardDelete' => false,
                ],
                'showLiltTranslateButton' => true,
                'isUnpublishedDraft' => false,
                'permissionSuffix' => ':edit-lilt-jobs',
                'authorOptionCriteria' => [
                    'can' => 'editEntries:edit-lilt-jobs',
                ],
                'author' => [
                    'username' => 'craftcms',
                    'photoId' => NULL,
                    'firstName' => NULL,
                    'lastName' => NULL,
                    'email' => 'support@craftcms.com',
                    'password' => NULL,
                    'admin' => '1',
                    'locked' => '0',
                    'suspended' => '0',
                    'pending' => '0',
                    'invalidLoginCount' => NULL,
                    'lastInvalidLoginDate' => NULL,
                    'lockoutDate' => NULL,
                    'hasDashboard' => '0',
                    'passwordResetRequired' => false,
                    'lastPasswordChangeDate' => NULL,
                    'unverifiedEmail' => NULL,
                    'newPassword' => NULL,
                    'currentPassword' => NULL,
                    'verificationCodeIssuedDate' => NULL,
                    'verificationCode' => NULL,
                    'lastLoginAttemptIp' => NULL,
                    'authError' => NULL,
                    'inheritorOnDelete' => NULL,
                    'id' => 1,
                    'tempId' => NULL,
                    'draftId' => NULL,
                    'revisionId' => NULL,
                    'isProvisionalDraft' => false,
                    'uid' => 'b75f3305-0381-4ab7-8c4d-236757944948',
                    'siteSettingsId' => 1,
                    'fieldLayoutId' => NULL,
                    'structureId' => NULL,
                    'contentId' => 1,
                    'enabled' => true,
                    'archived' => false,
                    'siteId' => 4,
                    'title' => NULL,
                    'slug' => NULL,
                    'uri' => NULL,
                    'dateLastMerged' => NULL,
                    'dateDeleted' => NULL,
                    'root' => NULL,
                    'lft' => NULL,
                    'rgt' => NULL,
                    'level' => NULL,
                    'searchScore' => NULL,
                    'trashed' => false,
                    'awaitingFieldValues' => false,
                    'propagating' => false,
                    'propagateAll' => false,
                    'newSiteIds' => [
                    ],
                    'isNewForSite' => false,
                    'resaving' => false,
                    'duplicateOf' => NULL,
                    'firstSave' => false,
                    'mergingCanonicalChanges' => false,
                    'updatingFromDerivative' => false,
                    'previewing' => false,
                    'hardDelete' => false,
                ],
                'jobLogs' => [
                    0 => [
                    ],
                ],
                'showLiltSyncButton' => false,
                'sendToLiltActionLink' => 'craft-lilt-plugin/job/send-to-lilt/14',
                'syncFromLiltActionLink' => 'craft-lilt-plugin/job/sync-from-lilt/14',
                'crumbs' => [
                    0 => [
                        'label' => 'Lilt Plugin',
                        'url' => 'http://$PRIMARY_SITE_URL/index.php?p=admin/admin/craft-lilt-plugin',
                    ],
                    1 => [
                        'label' => 'Jobs',
                        'url' => 'http://$PRIMARY_SITE_URL/index.php?p=admin/admin/craft-lilt-plugin/jobs',
                    ],
                ],
            ],
            'templateMode' => NULL,
        ];
        return $expected;
    }
}
