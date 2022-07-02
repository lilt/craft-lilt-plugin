<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers\job;

use Codeception\Util\HttpCode;
use Craft;
use craft\elements\Entry;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\controllers\job\GetTranslationReviewController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugintests\integration\ViewWrapper;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;

class GetTranslationReviewControllerCest
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
    private function getController(): GetTranslationReviewController
    {
        Craftliltplugin::getInstance()->controllerNamespace = 'lilthq\craftliltplugin\controllers';
        return Craft::$app->createController('craft-lilt-plugin/job/get-translation-review/invoke')[0];
    }

    /**
     * @throws \Throwable
     * @throws InvalidConfigException
     * @throws \Codeception\Exception\ModuleException
     * @throws \JsonException
     */
    public function testSuccess(IntegrationTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $element = Entry::find()
            ->where(['authorId' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        /**
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
            'liltJobId' => 777,
        ]);
        $translations[0]->targetContent = $this->getTargetContent();
        $translations[0]->save();

        $controller = $this->getController();
        $controller->request->setBodyParams(['translationId' => $translations[0]->id]);
        $response = $controller->actionInvoke();

        $behavior = $response->getBehavior('template');
        $actual = [
            'variables' => $behavior->variables,
            'template' => $behavior->template,
            'templateMode' => $behavior->templateMode,
        ];

        $expected = $this->getExpected();

        foreach ($expected['variables']['translation'] as $key => $value) {
            if (is_array($value)) {
                Assert::assertEqualsCanonicalizing($value, $actual['variables']['translation'][$key]);
                continue;
            }

            Assert::assertSame($value, $actual['variables']['translation'][$key]);
        }

        Assert::assertSame($expected['template'], $actual['template']);
        Assert::assertSame($expected['variables']['previewUrl'], $actual['variables']['previewUrl']);
        Assert::assertSame($expected['variables']['originalUrl'], $actual['variables']['originalUrl']);
        Assert::assertNull($actual['templateMode']);

        Assert::assertSame(HttpCode::OK, $response->getStatusCode());
    }

    private function getExpected(): array
    {
        return [
            'template' => 'craft-lilt-plugin/_components/translation/_overview.twig',
            'variables' => [
                'previewUrl' => 'http://test.craftcms.test:80/index.php?p=blog/es/first-entry-user-1',
                'originalUrl' => 'http://$PRIMARY_SITE_URL/index.php?p=blog/first-entry-user-1',
                'translation' => [
                    'translatedDraftId' => null,
                    'sourceContent' => $this->getSourceContent(),
                    'targetContent' => $this->getTargetContent(),
                    'lastDelivery' => null,
                    'status' => 'in-progress',
                    'connectorTranslationId' => null,
                ]
            ],
            'templateMode' => null,
        ];
    }

    private function getTargetContent(): array
    {
        $targetContent = [
            [
                'neo' => [
                    [
                        'fields' => [
                            'matrix' => [
                                [
                                    'fields' => [
                                        'plainTextFirstBlock' => 'es-ES: neo - firstBlockType - matrix - Plain text first block'
                                    ]
                                ],
                                [
                                    'fields' => [
                                        'plainTextSecondBlock' => 'es-ES: neo - firstBlockType - matrix - Plain text second block'
                                    ]
                                ]
                            ],
                            'redactor' => 'es-ES: firstBlockType - redactor - Here is value of field',
                            'lightswitch' => [
                                'onLabel' => 'es-ES: The label text to display beside the lightswitch’s enabled state',
                                'offLabel' => 'es-ES: The label text to display beside the lightswitch’s disabled state.'
                            ]
                        ]
                    ],
                    [
                        'fields' => [
                            'table' => [
                                'content' => [
                                    [
                                        'columnHeading1' => 'es-ES: secondBlockType - table - First row first value',
                                        'columnHeading2' => 'es-ES: secondBlockType - table - First row second value',
                                        'columnHeading3' => 'es-ES: secondBlockType - table - First row third value',
                                        'columnHeading4' => 'es-ES: secondBlockType - table - First row fourth value'
                                    ],
                                    [
                                        'columnHeading1' => 'es-ES: secondBlockType - table - Second row first value',
                                        'columnHeading2' => 'es-ES: secondBlockType - table - Second row second value',
                                        'columnHeading3' => 'es-ES: secondBlockType - table - Second row third value',
                                        'columnHeading4' => 'es-ES: secondBlockType - table - Second row fourth value'
                                    ]
                                ]
                            ],
                            'plainText' => 'es-ES: secondBlockType - plainText - Here is value of field',
                            'supertable' => [
                            ]
                        ]
                    ]
                ],
                'table' => [
                    'columns' => [
                        'columnHeading1' => 'es-ES: Column Heading 1',
                        'columnHeading2' => 'es-ES: Column Heading 2',
                        'columnHeading3' => 'es-ES: Column Heading 3',
                        'columnHeading4' => 'es-ES: Column Heading 4'
                    ],
                    'content' => [
                        [
                            'columnHeading1' => 'es-ES: First row first value',
                            'columnHeading2' => 'es-ES: First row second value',
                            'columnHeading3' => 'es-ES: First row third value',
                            'columnHeading4' => 'es-ES: First row fourth value'
                        ],
                        [
                            'columnHeading1' => 'es-ES: Second row first value',
                            'columnHeading2' => 'es-ES: Second row second value',
                            'columnHeading3' => 'es-ES: Second row third value',
                            'columnHeading4' => 'es-ES: Second row fourth value'
                        ]
                    ]
                ],
                'title' => 'es-ES: Some example title',
                'matrix' => [
                    [
                        'fields' => [
                            'plainTextFirstBlock' => 'es-ES: Plain text first block'
                        ]
                    ],
                    [
                        'fields' => [
                            'plainTextSecondBlock' => 'es-ES: Plain text second block'
                        ]
                    ]
                ],
                'redactor' => 'es-ES: <h1>Here is some header text</h1> Here is some content',
                'checkboxes' => [
                    'firstCheckboxLabel' => 'es-ES: First checkbox label',
                    'thirdCheckboxLabel' => 'es-ES: Third checkbox label',
                    'secondCheckboxLabel' => 'es-ES: Second checkbox label'
                ],
                'lightswitch' => [
                    'onLabel' => 'es-ES: The label text to display beside the lightswitch’s enabled state',
                    'offLabel' => 'es-ES: The label text to display beside the lightswitch’s disabled state.'
                ],
            ]
        ];


        if (TEST_COLOUR_SWATCHES_PLUGIN) {
            $targetContent[0]['colorSwatches'] = [
                'labels' =>
                    [
                        'a5e0af2bdf434712fd71358f5a2415b1' => 'ES: first label',
                        'e7c9c88325b2a6a2476e2516094b6ba4' => 'ES: second label',
                        'f13b85cdf5fdd245b03675f94d964946' => 'ES: third label',
                    ],
            ];
        }

        if (TEST_LINKIT_PLUGIN) {
            $targetContent[0]['linkit'] =
                [
                    'value' =>
                        [
                            'fruitstudios\\linkit\\models\\Email' =>
                                [
                                    'value' => 'es@lilt.com',
                                    'customText' => 'es-ES: Test linkit text label',
                                ],
                        ],
                    'defaultText' => 'Default link text',
                    'customLabels' =>
                        [
                            'fruitstudios\\linkit\\models\\Url' => 'ES: Website url label',
                            'fruitstudios\\linkit\\models\\Email' => 'ES: Email address label',
                            'fruitstudios\\linkit\\models\\Phone' => 'ES: Phone number label',
                        ],
                ];
        }

        if (TEST_SUPERTABLE_PLUGIN) {
            $targetContent[0]['supertable'] = [
                [
                    'fields' => [
                        'firstField' => 'es-ES: firstField - Supertable text',
                        'secondField' => 'es-ES: secondField - Supertable text'
                    ]
                ]
            ];
        }

        return $targetContent;
    }

    /**
     * @return array|array[]
     */
    private function getSourceContent(): array
    {
        $sourceContent = [
            [
                'neo' => [
                    [
                        'fields' => [
                            'matrix' => [
                                [
                                    'fields' => [
                                        'plainTextFirstBlock' => 'neo - firstBlockType - matrix - Plain text first block'
                                    ]
                                ],
                                [
                                    'fields' => [
                                        'plainTextSecondBlock' => 'neo - firstBlockType - matrix - Plain text second block'
                                    ]
                                ]
                            ],
                            'redactor' => 'firstBlockType - redactor - Here is value of field',
                            'lightswitch' => [
                                'onLabel' => 'The label text to display beside the lightswitch’s enabled state',
                                'offLabel' => 'The label text to display beside the lightswitch’s disabled state.'
                            ]
                        ]
                    ],
                    [
                        'fields' => [
                            'table' => [
                                'content' => [
                                    [
                                        'columnHeading1' => 'secondBlockType - table - First row first value',
                                        'columnHeading2' => 'secondBlockType - table - First row second value',
                                        'columnHeading3' => 'secondBlockType - table - First row third value',
                                        'columnHeading4' => 'secondBlockType - table - First row fourth value'
                                    ],
                                    [
                                        'columnHeading1' => 'secondBlockType - table - Second row first value',
                                        'columnHeading2' => 'secondBlockType - table - Second row second value',
                                        'columnHeading3' => 'secondBlockType - table - Second row third value',
                                        'columnHeading4' => 'secondBlockType - table - Second row fourth value'
                                    ]
                                ]
                            ],
                            'plainText' => 'secondBlockType - plainText - Here is value of field',
                            #'supertable' => []
                        ]
                    ]
                ],
                'table' => [
                    'columns' => [
                        'columnHeading1' => 'Column Heading 1',
                        'columnHeading2' => 'Column Heading 2',
                        'columnHeading3' => 'Column Heading 3',
                        'columnHeading4' => 'Column Heading 4'
                    ],
                    'content' => [
                        [
                            'columnHeading1' => 'First row first value',
                            'columnHeading2' => 'First row second value',
                            'columnHeading3' => 'First row third value',
                            'columnHeading4' => 'First row fourth value'
                        ],
                        [
                            'columnHeading1' => 'Second row first value',
                            'columnHeading2' => 'Second row second value',
                            'columnHeading3' => 'Second row third value',
                            'columnHeading4' => 'Second row fourth value'
                        ]
                    ]
                ],
                'title' => 'Some example title',
                'matrix' => [
                    [
                        'fields' => [
                            'plainTextFirstBlock' => 'Plain text first block'
                        ]
                    ],
                    [
                        'fields' => [
                            'plainTextSecondBlock' => 'Plain text second block'
                        ]
                    ]
                ],
                'redactor' => '<h1>Here is some header text</h1> Here is some content',
                'checkboxes' => [
                    'firstCheckboxLabel' => 'First checkbox label',
                    'thirdCheckboxLabel' => 'Third checkbox label',
                    'secondCheckboxLabel' => 'Second checkbox label'
                ],
                'lightswitch' => [
                    'onLabel' => 'The label text to display beside the lightswitch’s enabled state',
                    'offLabel' => 'The label text to display beside the lightswitch’s disabled state.'
                ],
            ]
        ];

        if (TEST_COLOUR_SWATCHES_PLUGIN) {
            $sourceContent[0]['colorSwatches'] = [
                'labels' =>
                    [
                        'a5e0af2bdf434712fd71358f5a2415b1' => 'first label',
                        'e7c9c88325b2a6a2476e2516094b6ba4' => 'second label',
                        'f13b85cdf5fdd245b03675f94d964946' => 'third label',
                    ],
            ];
        }

        if (TEST_LINKIT_PLUGIN) {
            $sourceContent[0]['linkit'] = [
                'value' => [
                    [
                        'fruitstudios\\linkit\\models\\Email' =>
                            [
                                'value' => 'test@lilt.com',
                                'customText' => 'Test linkit text label',
                            ],
                    ],
                    'defaultText' => 'Default link text',
                    'customLabels' =>
                        [
                            'fruitstudios\\linkit\\models\\Url' => 'Website url label',
                            'fruitstudios\\linkit\\models\\Email' => 'Email address label',
                            'fruitstudios\\linkit\\models\\Phone' => 'Phone number label',
                        ]
                ]
            ];
        }

        if (TEST_SUPERTABLE_PLUGIN) {
            $sourceContent[0]['supertable'] = [
                [
                    'fields' => [
                        'firstField' => 'firstField - Supertable text',
                        'secondField' => 'secondField - Supertable text'
                    ]
                ]
            ];
        }

        return $sourceContent;
    }
}
