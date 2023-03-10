<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\services\appliers\TranslationApplyCommand;

/**
 * m230304_182344_update_entry_content migration.
 */
class m230304_182344_update_entry_content extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $plugin = new Craftliltplugin('craft-lilt-plugin');

        $entry = Craft::$app->getElements()->getElementById(
            24,
            Entry::class,
            $plugin->languageMapper->getSiteIdByLanguage('en')
        );

        $job = new Job();
        $job->sourceSiteId = $plugin->languageMapper->getSiteIdByLanguage('en');

        $plugin->elementTranslatableContentApplier->apply(
            new TranslationApplyCommand(
                $entry,
                $job,
                $this->getContentEN(),
                'en'
            )
        );

        Craft::$app->getElements()->invalidateAllCaches();
    }

    private function getContentEN(): array
    {
        return [
            "title" => "The Future of Augmented Reality",
            "shortDescription" => "<p>Personalized ads everywhere you look</p>",
            "heading" => "Your iPhone Is No Longer a Way To Hide",
            "subheading" => "But is now a way to connect with the world",
            "articleBody" => [
                "25" => [
                    "fields" => [
                        "text" =>
                            "<p>When you're watching the world through a screen, you forget what's real and what's not. This creates some exciting opportunities for advertisers.<br /><br />Imagine this scenario: you're walking to a coffee shop and hear one of your favorite songs from your college days. You turn to see a car coming down the street, and the driver looks like a younger version of yourself. <br /><br />He gives you the slightest nod as he passes, and it brings back warm memories of your carefree youth.<br /><br />Later, when you order your coffee, you see an ad for the car projected on to your cup. If you want to do a test drive, just click 'yes' and the car will come pick you up.<br /></p>",
                        "position" => [
                            "left" => "Left",
                            "center" => "Center",
                            "right" => "Right",
                        ],
                    ],
                ],
                "30" => [
                    "fields" => [
                        "pullQuote" =>
                            "You turn to see a car coming down the street, and the driver looks like a younger version of yourself.",
                        "position" => [
                            "left" => "Left",
                            "center" => "Center",
                            "right" => "Right",
                        ],
                    ],
                ],
                "31" => [
                    "fields" => [
                        "position" => [
                            "left" => "Left",
                            "center" => "Center",
                            "right" => "Right",
                            "full" => "Full",
                        ],
                    ],
                ],
                "32" => ["fields" => ["heading" => "A People-to-People Business"]],
                "33" => [
                    "fields" => [
                        "text" =>
                            "<p>Each person wants a slightly different version of reality. Now they can get it.<br /><br /><br /></p>",
                    ],
                ],
                "34" => [
                    "fields" => [
                        "quote" =>
                            "Augmented reality has long sounded like a wild futuristic concept, but the technology has actually been around for years.",
                        "attribution" => "Charlie Roths, Developers.Google",
                        "position" => ["center" => "Center", "full" => "Full"],
                    ],
                ],
                "35" => [
                    "fields" => [
                        "heading" => "What is Happy Lager Doing About It?",
                    ],
                ],
                "36" => [
                    "fields" => [
                        "text" =>
                            "<p>When you drink our beer, we use AI to evaluate your emotional state, and use a proprietary algorithm to generate an artificial environment that provides the exact olfactory, visual, and auditory stimulation you want.<br /><br />Forget about the real world as we blow the smell of your mother's cinnamon rolls past your face. <br /><br />Sink into your chair as Dean Martin sings relaxing jazz standards.<br /><br />Play Candy Smash in stunning 8k resolution, with only an occasional ad to extend your viewing experience.<br /></p>",
                    ],
                ],
                "37" => ["fields" => []],
                "38" => ["fields" => ["heading" => "This is Only the Beginning"]],
                "39" => [
                    "fields" => [
                        "text" =>
                            "<p>The real world has practical limits on advertisers. The augmented world is only limited by your design budget and production values.</p>",
                    ],
                ],
                "41" => ["fields" => []],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m230304_182344_update_entry_content can't be reverted.\n";

        return true;
    }
}
