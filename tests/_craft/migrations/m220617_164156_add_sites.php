<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;

/**
 * m220617_164156_add_sites migration.
 */
class m220617_164156_add_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $section = Craft::$app->sections->getSectionByHandle('blog');
        if($section) {
            $this->safeDown();
        }

        $section = new Section();

        $section->type = Section::TYPE_CHANNEL;
        $section->handle = 'blog';
        $section->name = 'Blog section';

        $groups = Craft::$app->sites->getAllGroups();

        $siteEnUS = Craft::$app->sites->getSiteByHandle('default');

        if($siteEnUS === null) {
            throw new \Exception('Cant get default website');
        }

        $siteEnUS->name = 'Craft test';
        $siteEnUS->setBaseUrl('$PRIMARY_SITE_URL');

        Craft::$app->sites->saveSite($siteEnUS);

        $siteSetting = new Section_SiteSettings();
        $siteSetting->siteId = $siteEnUS->id;
        $siteSetting->enabledByDefault = true;

        $sitesToCreate = ['deDE' => 'de-DE', 'ruRU' => 'ru-RU', 'esES' => 'es-ES'];
        $siteSettings = [$siteSetting];

        foreach ($sitesToCreate as $handle => $language) {
            $site = new Site();
            $site->language = $language;
            $site->handle = $handle;
            $site->setName(
                sprintf('Craft test %s', explode('-',$language)[0])
            );
            $site->groupId = $groups[0]->id;
            $site->setBaseUrl('@web/' . explode('-',$language)[0]);

            Craft::$app->sites->saveSite($site);

            $site = Craft::$app->sites->getSiteByHandle($site->handle);

            $siteSetting = new Section_SiteSettings();
            $siteSetting->siteId = $site->id;
            $siteSetting->enabledByDefault = true;

            #$siteSetting->uriFormat = sprintf('/blog/%s', explode('-',$language)[0]);
            $siteSetting->uriFormat = sprintf('/blog/%s/{slug}', explode('-',$language)[0]);
            $siteSetting->hasUrls = true;

            $siteSettings[] = $siteSetting;
        }

        $siteSetting = new Section_SiteSettings();
        $siteSetting->siteId = $siteEnUS->id;
        $siteSetting->enabledByDefault = true;

        $siteSetting->uriFormat = 'blog/{slug}';
        $siteSetting->hasUrls = true;

        $siteSettings[] = $siteSetting;

        $entryType = new EntryType();

        $entryType->handle = 'default';
        $entryType->hasTitleField = true;
        $entryType->name = 'Default';
        $entryType->sortOrder = 1;
        $entryType->titleTranslationMethod = Field::TRANSLATION_METHOD_LANGUAGE;

        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings($siteSettings);

        Craft::$app->sections->saveSection($section);

        Craft::$app->plugins->installPlugin('colour-swatches');
        Craft::$app->plugins->installPlugin('linkit');
        Craft::$app->plugins->installPlugin('neo');
        Craft::$app->plugins->installPlugin('redactor');
        Craft::$app->plugins->installPlugin('super-table');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $section = Craft::$app->sections->getSectionByHandle('blog');
        if($section) {
            Craft::$app->getSections()->deleteSectionById((int) $section->id);
        }

        $sitesToCreate = ['esES' => 'es-ES', 'deDE' => 'de-DE', 'ruRU' => 'ru-RU'];
        foreach ($sitesToCreate as $handle => $language) {
            $site = Craft::$app->getSites()->getSiteByHandle($handle);
            if($site) {
                Craft::$app->getSites()->deleteSiteById($site->id);
            }
        }

        echo "m220617_164156_add_sites reverted.\n";

        return true;
    }
}
