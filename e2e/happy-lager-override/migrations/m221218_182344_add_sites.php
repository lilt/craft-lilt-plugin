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
 * m221218_182344_add_sites migration.
 */
class m221218_182344_add_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $groups = Craft::$app->sites->getAllGroups();

        $site = Craft::$app->sites->getSiteByHandle('en');
        $site->setBaseUrl('@web/');
        Craft::$app->sites->saveSite($site);

        // handle => language
        $sitesToCreate = [
            'uk' => 'uk',
            'de' => 'de',
            'es' => 'es',
//            'fr' => 'fr',
//            'it' => 'it'
        ];

        $groups[0]->name = 'Happy Lager';
        Craft::$app->sites->saveGroup($groups[0]);

        $section = Craft::$app->sections->getSectionByHandle('news');
        $siteSettings = $section->getSiteSettings();

        foreach ($sitesToCreate as $handle => $language) {
            $site = new Site();
            $site->language = $language;
            $site->handle = $handle;
            $site->setName(
                sprintf('Happy Lager (%s)', $language)
            );
            $site->groupId = $groups[0]->id;
            $site->setBaseUrl('@web/' . explode('-', $language)[0]);

            Craft::$app->sites->saveSite($site, false);

            $siteSetting = new Section_SiteSettings();
            $siteSetting->siteId = $site->id;
            $siteSetting->sectionId = $section->id;
            $siteSetting->enabledByDefault = true;
            $siteSetting->uriFormat = 'news/{slug}';
            $siteSetting->template = 'news/_entry';

            $siteSettings[] = $siteSetting;
        }

        $section->setSiteSettings($siteSettings);
        Craft::$app->sections->saveSection($section);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $section = Craft::$app->sections->getSectionByHandle('blog');
        if ($section) {
            Craft::$app->getSections()->deleteSectionById((int) $section->id);
        }

        $sitesToCreate = [
            'uk' => 'uk',
            'de' => 'de',
            'es' => 'es',
//            'fr' => 'fr',
//            'it' => 'it'
        ];
        foreach ($sitesToCreate as $handle => $language) {
            $site = Craft::$app->getSites()->getSiteByHandle($handle);
            if ($site) {
                Craft::$app->getSites()->deleteSiteById($site->id);
            }
        }

        echo "m220617_164156_add_sites reverted.\n";

        return true;
    }
}
