<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use benf\neo\Field as NeoField;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Checkboxes;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\helpers\StringHelper;
use craft\models\FieldGroup;
use craft\models\FieldLayout;
use craft\redactor\Field as RedactorField;
use fruitstudios\linkit\fields\LinkitField;
use percipioglobal\colourswatches\fields\ColourSwatches;
use RuntimeException;
use verbb\supertable\fields\SuperTableField;

/**
 * m220617_180419_add_fields migration.
 */
class m220617_180419_add_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        #$this->safeDown();

        $group = new FieldGroup([
            "name" => "Lilt Field Group",
        ]);
        // Save the group
        $result = (Craft::$app->fields->saveGroup($group));

        $group = (new Query())
            ->select("id")
            ->from("{{%fieldgroups}}")
            ->where(["name" => "Lilt Field Group", "dateDeleted" => null])
            ->one();

        if (!$group['id']) {
            return false;
        }

        $entryType = (Craft::$app->sections->getAllEntryTypes()[0]);

        if (!$entryType) {
            return false;
        }

        $groupId = (int)$group['id'];

        $fieldLayout = $entryType->getFieldLayout();
        $tabs = $fieldLayout->getTabs();

        $newFields[] = $this->createPlaintextField($groupId);
        $newFields[] = $this->createRedactorField($groupId);
        $newFields[] = $this->createMatrixField($groupId);
        $newFields[] = $this->createCheckboxesField($groupId);
        $newFields[] = $this->createLightswitchField($groupId);

        if (TEST_SUPERTABLE_PLUGIN) {
            $newFields[] = $this->createSuperTableField($groupId);
        }

        $newFields[] = $this->createTableField($groupId);
        $newFields[] = $this->createNeoField($groupId);

        if (TEST_LINKIT_PLUGIN) {
            $newFields[] = $this->createLinkitField($groupId);
        }

        if (TEST_COLOUR_SWATCHES_PLUGIN) {
            $newFields[] = $this->createColourSwatches($groupId);
        }

        $tab = $tabs[0];

        $customFields = [];
        foreach ($newFields as $item) {
            $customField = new CustomField();
            $customField->setField($item);

            $customFields[] = $customField;
        }

        $tab->setElements(
            $customFields
        );

        $result = Craft::$app->fields->saveLayout($fieldLayout) && $result;

        return $result;
    }

    public function createNeoField(int $groupId): NeoField
    {
        $firstBlockLayoutId = $this->createNeoFirstBlockLayout();
        $secondBlockLayoutId = $this->createNeoSecondBlockLayout();

        $field = new NeoField(
            [
                'name' => 'Neo',
                'handle' => 'neo',
                'instructions' => '',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'site',
                'translationKeyFormat' => null,

                'minBlocks' => '',
                'maxBlocks' => '',
                'maxTopBlocks' => '',
                'maxLevels' => '',
                'wasModified' => false,
                'propagationMethod' => NeoField::PROPAGATION_METHOD_SITE_GROUP,
                #'propagationKeyFormat' => NULL,
            ]
        );

        $field->setBlockTypes(
            [
                'new1' => [
                    'description' => '',
                    'name' => 'first block type',
                    'handle' => 'firstBlockType',
                    'sortOrder' => 1,
                    'maxBlocks' => 0,
                    'maxSiblingBlocks' => 0,
                    'maxChildBlocks' => 0,
                    'childBlocks' => null,
                    'topLevel' => true,
                    'fieldLayoutId' => $firstBlockLayoutId
                ],
                'new2' => [
                    'description' => '',
                    'name' => 'second block type',
                    'handle' => 'secondBlockType',
                    'sortOrder' => 2,
                    'maxBlocks' => 0,
                    'maxSiblingBlocks' => 0,
                    'maxChildBlocks' => 0,
                    'childBlocks' => null,
                    'topLevel' => true,
                    'fieldLayoutId' => $secondBlockLayoutId
                ],
            ]
        );

        $field->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($field);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        $field = Craft::$app->fields->getFieldByHandle('neo');

        return $field;
    }

    public function createSuperTableField(int $groupId): SuperTableField
    {
        $field = new SuperTableField(
            [
                'name' => 'Supertable',
                'handle' => 'supertable',
                'instructions' => '',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'site',
                'translationKeyFormat' => null,
                'minRows' => '',
                'maxRows' => '',
                'contentTable' => '{{%stc_supertable}}',
                'propagationMethod' => SuperTableField::PROPAGATION_METHOD_SITE_GROUP,
                'propagationKeyFormat' => null,
                'staticField' => '',
                'columns' => [
                ],
                'fieldLayout' => 'table',
                'selectionLabel' => 'New Row Label',
                'placeholderKey' => null,
                'blockTypes' => [
                    'new1' => [
                        'fields' => [
                            'new1' => [
                                'name' => 'First Field',
                                'handle' => 'firstField',
                                'required' => 0,
                                'instructions' => '',
                                'searchable' => 0,
                                'translationMethod' => 'language',
                                'translationKeyFormat' => null,
                                'type' => 'craft\\fields\\PlainText',
                                'typesettings' => [
                                    'uiMode' => 'normal',
                                    'placeholder' => null,
                                    'code' => '',
                                    'multiline' => '',
                                    'initialRows' => 4,
                                    'charLimit' => null,
                                    'byteLimit' => null,
                                    'columnType' => null,
                                ],
                            ],
                            'new2' => [
                                'name' => 'Second Field',
                                'handle' => 'secondField',
                                'required' => 0,
                                'instructions' => '',
                                'searchable' => 0,
                                'translationMethod' => 'language',
                                'translationKeyFormat' => null,
                                'type' => 'craft\\redactor\\Field',
                                'typesettings' => [
                                    'uiMode' => 'enlarged',
                                    'redactorConfig' => '',
                                    'removeInlineStyles' => 1,
                                    'removeEmptyTags' => 1,
                                    'removeNbsp' => 1,
                                    'availableVolumes' => '*',
                                    'availableTransforms' => '*',
                                    'showUnpermittedVolumes' => false,
                                    'showUnpermittedFiles' => false,
                                    'showHtmlButtonForNonAdmins' => '',
                                    'configSelectionMode' => 'choose',
                                    'manualConfig' => '',
                                    'defaultTransform' => '',
                                    'purifierConfig' => '',
                                    'purifyHtml' => 1,
                                    'columnType' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],

            ]
        );

        $field->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($field);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $field;
    }

    public function createRedactorField(int $groupId): RedactorField
    {
        $field = new RedactorField(
            [
                'name' => 'Redactor',
                'handle' => 'redactor',
                'instructions' => '',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'language',
                'translationKeyFormat' => null,
                'uiMode' => 'enlarged',
                'redactorConfig' => '',
                'removeInlineStyles' => 1,
                'removeEmptyTags' => 1,
                'removeNbsp' => 1,
                'availableVolumes' => '*',
                'availableTransforms' => '*',
                'showUnpermittedVolumes' => false,
                'showUnpermittedFiles' => false,
                'showHtmlButtonForNonAdmins' => '',
                'configSelectionMode' => 'choose',
                'manualConfig' => '',
                'defaultTransform' => '',
                'purifierConfig' => '',
                'purifyHtml' => 1,
                'columnType' => 'text',
            ]
        );

        $field->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($field);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $field;
    }

    public function createMatrixField(int $groupId): Matrix
    {
        $field = new Matrix(
            [
                'name' => 'Matrix',
                'handle' => 'matrix',
                'instructions' => '',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'site',
                'translationKeyFormat' => null,
                'minBlocks' => '',
                'maxBlocks' => '',
                'contentTable' => '{{%matrixcontent_matrix}}',
                'propagationMethod' => Matrix::PROPAGATION_METHOD_SITE_GROUP,
                'propagationKeyFormat' => null,
                'blockTypes' => [
                    'new1' => [
                        'name' => 'First block',
                        'handle' => 'firstBlock',
                        'fields' => [
                            'new1' => [
                                'name' => 'Plain text first block',
                                'handle' => 'plainTextFirstBlock',
                                'required' => 0,
                                'instructions' => '',
                                'searchable' => 0,
                                'translationMethod' => 'language',
                                'translationKeyFormat' => null,
                                'type' => 'craft\\fields\\PlainText',
                                'typesettings' => [
                                    'uiMode' => 'normal',
                                    'placeholder' => null,
                                    'code' => '',
                                    'multiline' => '',
                                    'initialRows' => 4,
                                    'charLimit' => null,
                                    'byteLimit' => null,
                                    'columnType' => null,
                                ],
                                'width' => 25,
                            ],
                        ],
                    ],
                    'new2' => [
                        'name' => 'secondBlock',
                        'handle' => 'secondblock',
                        'fields' => [
                            'new1' => [
                                'name' => 'Plain Text Second Block',
                                'handle' => 'plainTextSecondBlock',
                                'required' => 0,
                                'instructions' => '',
                                'searchable' => 0,
                                'translationMethod' => 'language',
                                'translationKeyFormat' => null,
                                'type' => 'craft\\fields\\PlainText',
                                'typesettings' => [
                                    'uiMode' => 'normal',
                                    'placeholder' => null,
                                    'code' => '',
                                    'multiline' => '',
                                    'initialRows' => 4,
                                    'charLimit' => null,
                                    'byteLimit' => null,
                                    'columnType' => null,
                                ],
                                'width' => 100,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $field->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($field);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $field;
    }

    public function createTableField(int $groupId): Table
    {
        $field = new Table([
            'name' => 'Table',
            'handle' => 'table',
            'instructions' => '',
            'required' => null,
            'searchable' => 0,
            'translationMethod' => 'language',
            'translationKeyFormat' => null,
            'addRowLabel' => 'Add a row',
            'maxRows' => '',
            'minRows' => '',
            'columns' => [
                'col1' => [
                    'heading' => 'Column Heading 1',
                    'handle' => 'columnHeading1',
                    'width' => '',
                    'type' => 'singleline',
                ],
                'col2' => [
                    'heading' => 'Column Heading 2',
                    'handle' => 'columnHeading2',
                    'width' => '',
                    'type' => 'singleline',
                ],
                'col3' => [
                    'heading' => 'Column Heading 3',
                    'handle' => 'columnHeading3',
                    'width' => '',
                    'type' => 'singleline',
                ],
                'col4' => [
                    'heading' => 'Column Heading 4',
                    'handle' => 'columnHeading4',
                    'width' => '',
                    'type' => 'singleline',
                ],
            ],
            'defaults' => [
                0 => [
                    'col1' => 'First row first value',
                    'col2' => 'First row second value',
                    'col3' => 'First row third value',
                    'col4' => 'First row fourth value',
                ],
                1 => [
                    'col1' => 'Second row first value',
                    'col2' => 'Second row second value',
                    'col3' => 'Second row third value',
                    'col4' => 'Second row fourth value',
                ],
            ],
            'columnType' => 'text',
        ]);

        $field->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($field);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $field;
    }

    public function createPlaintextField(int $groupId): PlainText
    {
        $introField = new PlainText([
            'groupId' => $groupId,
            'name' => 'Plain Text',
            'handle' => 'plainText',
            'instructions' => '',
            'required' => null,
            'searchable' => 0,
            'translationMethod' => 'language',
            'translationKeyFormat' => null,
            'uiMode' => 'normal',
            'placeholder' => null,
            'code' => '',
            'multiline' => '',
            'initialRows' => 4,
            'charLimit' => null,
            'byteLimit' => null,
            'columnType' => null,
        ]);

        $created = Craft::$app->getFields()->saveField($introField);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $introField;
    }

    public function createLightswitchField(int $groupId): Lightswitch
    {
        $l = new Lightswitch(
            [
                'name' => 'Lightswitch',
                'handle' => 'lightswitch',
                'instructions' => '',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'language',
                'translationKeyFormat' => null,
                'default' => false,
                'onLabel' => 'The label text to display beside the lightswitch’s enabled state',
                'offLabel' => 'The label text to display beside the lightswitch’s disabled state.',
            ]
        );

        $l->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($l);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $l;
    }

    public function createCheckboxesField(int $groupId): Checkboxes
    {
        $checkboxes = new Checkboxes([
            'groupId' => $groupId,
            'name' => 'Checkboxes',
            'handle' => 'checkboxes',
            'instructions' => '',
            'required' => null,
            'searchable' => 0,
            'translationMethod' => 'language',
            'translationKeyFormat' => null,
            'multi' => true,
            'options' => [
                0 => [
                    'label' => 'First checkbox label',
                    'value' => 'firstCheckboxLabel',
                    'default' => '',
                ],
                1 => [
                    'label' => 'Second checkbox label',
                    'value' => 'secondCheckboxLabel',
                    'default' => '',
                ],
                2 => [
                    'label' => 'Third checkbox label',
                    'value' => 'thirdCheckboxLabel',
                    'default' => '',
                ],
            ],
        ]);
        $checkboxes->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($checkboxes);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $checkboxes;
    }

    public function createLinkitField(int $groupId): LinkitField
    {
        $linkitField = new LinkitField([
            'groupId' => $groupId,
            'name' => 'linkit',
            'handle' => 'linkit',
            'instructions' => 'Default Instructions. Helper text to guide the author.',
            'required' => null,
            'searchable' => 0,
            'translationMethod' => 'language',
            'translationKeyFormat' => null,
            'selectLinkText' => '',
            'types' => [
                'fruitstudios\\linkit\\models\\Email' => [
                    'enabled' => 1,
                    'customLabel' => 'Email address label',
                    'customPlaceholder' => 'support@lilt.com',
                ],
                'fruitstudios\\linkit\\models\\Phone' => [
                    'enabled' => 1,
                    'customLabel' => 'Phone number label',
                    'customPlaceholder' => '+44 415-992-5088',
                ],
                'fruitstudios\\linkit\\models\\Url' => [
                    'enabled' => 1,
                    'customLabel' => 'Website url label',
                    'customPlaceholder' => 'https://lilt.com/company',
                    'allowAlias' => 1,
                    'allowMailto' => 1,
                    'allowHash' => 1,
                    'allowPaths' => 1,
                ],
                'fruitstudios\\linkit\\models\\Twitter' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'customPlaceholder' => '',
                ],
                'fruitstudios\\linkit\\models\\Facebook' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'customPlaceholder' => '',
                ],
                'fruitstudios\\linkit\\models\\Instagram' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'customPlaceholder' => '',
                ],
                'fruitstudios\\linkit\\models\\LinkedIn' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'customPlaceholder' => '',
                ],
                'fruitstudios\\linkit\\models\\Entry' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'sources' => '*',
                    'customSelectionLabel' => '',
                ],
                'fruitstudios\\linkit\\models\\Category' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'sources' => '*',
                    'customSelectionLabel' => '',
                ],
                'fruitstudios\\linkit\\models\\Asset' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'sources' => '*',
                    'customSelectionLabel' => '',
                ],
                'fruitstudios\\linkit\\models\\User' => [
                    'enabled' => '',
                    'customLabel' => '',
                    'sources' => '*',
                    'customSelectionLabel' => '',
                    'userPath' => '',
                ],
            ],
            'allowCustomText' => 1,
            'defaultText' => 'Default link text',
            'allowTarget' => '',
        ]);
        $linkitField->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($linkitField);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $linkitField;
    }

    public function createColourSwatches(int $groupId): ColourSwatches
    {
        $colourSwatches = new ColourSwatches(
            [
                'name' => 'ColorSwatches',
                'handle' => 'colorSwatches',
                'instructions' => 'Default Instructions
Helper text to guide the author.',
                'required' => null,
                'searchable' => 0,
                'translationMethod' => 'language',
                'translationKeyFormat' => null,
                'options' => [
                    0 => [
                        'label' => 'first label',
                        'color' => '#CD5C5C',
                        'default' => 1,
                    ],
                    1 => [
                        'label' => 'second label',
                        'color' => '#F08080',
                        'default' => '',
                    ],
                    2 => [
                        'label' => 'third label',
                        'color' => '#FA8072',
                        'default' => '',
                    ],
                ],
                'useConfigFile' => '',
                'palette' => '',
                'default' => null,
            ]
        );
        $colourSwatches->groupId = $groupId;

        $created = Craft::$app->getFields()->saveField($colourSwatches);

        if (!$created) {
            throw new RuntimeException(
                sprintf("Failed to run %s", __FUNCTION__)
            );
        }

        return $colourSwatches;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $group = (new Query())
            ->select('id')
            ->from('{{%fieldgroups}}')
            ->where(['name' => 'Lilt Field Group', "dateDeleted" => null])
            ->one();

        $result = true;

        if (!empty($group['id'])) {
            $result = $result && (Craft::$app->fields->deleteGroupById((int)$group['id']));
        }

        /*
        // Find the field
        $introField = Craft::$app->fields->getFieldByHandle("introText");

        if ($introField) {
            $result = $result && (Craft::$app->fields->deleteFieldById((int)$introField->id));
        }
*/
        return $result;
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     */
    private function createNeoFirstBlockLayout()
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->uid = StringHelper::UUID();

        $redactor = Craft::$app->fields->getFieldByHandle('redactor');
        $lightswitch = Craft::$app->fields->getFieldByHandle('lightswitch');
        $matrix = Craft::$app->fields->getFieldByHandle('matrix');

        $fieldLayout->setTabs([
            [
                'name' => 'First Tab',
                'elements' => [
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'required' => false,
                        'fieldUid' => $redactor->uid
                    ],
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'required' => false,
                        'fieldUid' => $lightswitch->uid
                    ],
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'required' => false,
                        'fieldUid' => $matrix->uid
                    ],
                ]
            ]
        ]);
        $fieldLayout->type = 'firstBlockType';

        $saved = Craft::$app->fields->saveLayout($fieldLayout);
        $fieldLayoutData = (new Query())
            ->select("id")
            ->from("{{%fieldlayouts}}")
            ->where(["uid" => $fieldLayout->uid])
            ->one();
        $fieldLayoutId = $fieldLayoutData['id'];
        return $fieldLayoutId;
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     */
    private function createNeoSecondBlockLayout()
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->uid = StringHelper::UUID();

        $plainText = Craft::$app->fields->getFieldByHandle('plainText');
        $table = Craft::$app->fields->getFieldByHandle('table');

        $firstTab = [
            [
                'name' => 'First Tab',
                'elements' => [
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'required' => false,
                        'fieldUid' => $plainText->uid
                    ],
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'required' => false,
                        'fieldUid' => $table->uid
                    ],
                ]
            ]
        ];

        if (TEST_SUPERTABLE_PLUGIN) {
            $supertable = Craft::$app->fields->getFieldByHandle('supertable');
            $firstTab[0]['elements'][] = [
                'type' => 'craft\\fieldlayoutelements\\CustomField',
                'required' => false,
                'fieldUid' => $supertable->uid
            ];
        }

        $fieldLayout->setTabs($firstTab);
        $fieldLayout->type = 'secondBlockType';

        Craft::$app->fields->saveLayout($fieldLayout);
        $fieldLayoutData = (new Query())
            ->select("id")
            ->from("{{%fieldlayouts}}")
            ->where(["uid" => $fieldLayout->uid])
            ->one();
        $fieldLayoutId = $fieldLayoutData['id'];
        return $fieldLayoutId;
    }
}
