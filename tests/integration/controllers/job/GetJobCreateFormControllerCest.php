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
use JsonException;
use lilthq\craftliltplugin\controllers\job\GetJobCreateFormController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugintests\integration\ViewWrapper;
use lilthq\tests\fixtures\EntriesFixture;
use IntegrationTester;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class GetJobCreateFormControllerCest
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
    private function getController(): GetJobCreateFormController
    {
        Craftliltplugin::getInstance()->controllerNamespace = 'lilthq\craftliltplugin\controllers';
        return Craft::$app->createController('craft-lilt-plugin/job/get-job-create-form/invoke')[0];
    }

    /**
     * @throws ModuleException
     * @throws InvalidConfigException
     * @throws JsonException
     */
    public function testSuccess(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $controller = $this->getController();
        //$controller->setView(new ViewWrapper());

        $response = $controller->actionInvoke();

        $behavior = $response->getBehavior('template');
        $actual = [
            'variables' => $behavior->variables ?? [],
            'template' => $behavior->template ?? '',
            'templateMode' => $behavior->templateMode ?? '',
        ];

        $expected = $this->getExpected();
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
        Assert::assertEquals($expected['templateMode'], $actual['templateMode']);
    }

    /**
     * @return array
     */
    private function getExpected(): array
    {
        $expected = [
            'template' => 'craft-lilt-plugin/job/create.twig',
            'variables' => [
                'defaultTranslationWorkflow' => 'instant',
                'translationWorkflowsOptions' => [
                    'instant' => 'Instant',
                    'verified' => 'Verified',
                ],
                'availableSites' => [
                    0 => [
                        'value' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                        'label' => 'Craft test(en-US)',
                    ],
                    1 => [
                        'value' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE'),
                        'label' => 'Craft test de(de-DE)',
                    ],
                    2 => [
                        'value' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('ru-RU'),
                        'label' => 'Craft test ru(ru-RU)',
                    ],
                    3 => [
                        'value' => Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES'),
                        'label' => 'Craft test es(es-ES)',
                    ],
                ],
                'targetSites' => [
                    Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US') => 'en-US',
                    Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE') => 'de-DE',
                    Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('ru-RU') => 'ru-RU',
                    Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES') => 'es-ES',
                ],
                'element' => [
                    'uid' => null,
                    'authorId' => null,
                    'title' => null,
                    'liltJobId' => null,
                    'status' => 'draft',
                    'sourceSiteId' => null,
                    'sourceSiteLanguage' => null,
                    'targetSiteIds' => null,
                    'elementIds' => null,
                    'versions' => [
                    ],
                    'dueDate' => null,
                    'translationWorkflow' => null,
                    'dateCreated' => null,
                    'dateUpdated' => null,
                    'id' => null,
                    'tempId' => null,
                    'draftId' => null,
                    'revisionId' => null,
                    'isProvisionalDraft' => false,
                    'siteSettingsId' => null,
                    'fieldLayoutId' => null,
                    'structureId' => null,
                    'contentId' => null,
                    'enabled' => true,
                    'archived' => false,
                    'siteId' => 4,
                    'slug' => null,
                    'uri' => null,
                    'dateLastMerged' => null,
                    'dateDeleted' => null,
                    'root' => null,
                    'lft' => null,
                    'rgt' => null,
                    'level' => null,
                    'searchScore' => null,
                    'trashed' => false,
                    'awaitingFieldValues' => false,
                    'propagating' => false,
                    'propagateAll' => false,
                    'newSiteIds' => [
                    ],
                    'isNewForSite' => false,
                    'resaving' => false,
                    'duplicateOf' => null,
                    'firstSave' => false,
                    'mergingCanonicalChanges' => false,
                    'updatingFromDerivative' => false,
                    'previewing' => false,
                    'hardDelete' => false,
                ],
                'showLiltTranslateButton' => false,
                'isUnpublishedDraft' => true,
                'permissionSuffix' => ':edit-lilt-jobs',
                'authorOptionCriteria' => [
                    'can' => 'editEntries:edit-lilt-jobs',
                ],
                'author' => [
                    'username' => 'craftcms',
                    'photoId' => null,
                    'firstName' => null,
                    'lastName' => null,
                    'email' => 'support@craftcms.com',
                    'password' => null,
                    'admin' => '1',
                    'locked' => '0',
                    'suspended' => '0',
                    'pending' => '0',
                    'invalidLoginCount' => null,
                    'lastInvalidLoginDate' => null,
                    'lockoutDate' => null,
                    'hasDashboard' => '0',
                    'passwordResetRequired' => false,
                    'lastPasswordChangeDate' => null,
                    'unverifiedEmail' => null,
                    'newPassword' => null,
                    'currentPassword' => null,
                    'verificationCodeIssuedDate' => null,
                    'verificationCode' => null,
                    'lastLoginAttemptIp' => null,
                    'authError' => null,
                    'inheritorOnDelete' => null,
                    'id' => 1,
                    'tempId' => null,
                    'draftId' => null,
                    'revisionId' => null,
                    'isProvisionalDraft' => false,
                    'siteSettingsId' => 1,
                    'fieldLayoutId' => null,
                    'structureId' => null,
                    'contentId' => 1,
                    'enabled' => true,
                    'archived' => false,
                    'siteId' => 4,
                    'title' => null,
                    'slug' => null,
                    'uri' => null,
                    'dateLastMerged' => null,
                    'dateDeleted' => null,
                    'root' => null,
                    'lft' => null,
                    'rgt' => null,
                    'level' => null,
                    'searchScore' => null,
                    'trashed' => false,
                    'awaitingFieldValues' => false,
                    'propagating' => false,
                    'propagateAll' => false,
                    'newSiteIds' => [
                    ],
                    'isNewForSite' => false,
                    'resaving' => false,
                    'duplicateOf' => null,
                    'firstSave' => false,
                    'mergingCanonicalChanges' => false,
                    'updatingFromDerivative' => false,
                    'previewing' => false,
                    'hardDelete' => false,
                ],
                'crumbs' => [
                    0 => [
                        'label' => 'Lilt Plugin',
                        'url' => 'http://$PRIMARY_SITE_URL/index.php?p=admin/admin/craft-lilt-plugin&site=default',
                    ],
                    1 => [
                        'label' => 'Jobs',
                        'url' => 'http://$PRIMARY_SITE_URL/index.php?p=admin/admin/craft-lilt-plugin/jobs&site=default',
                    ],
                ],
            ],
            'templateMode' => null,
        ];
        return $expected;
    }
}
