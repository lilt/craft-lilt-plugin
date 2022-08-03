<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use Throwable;
use yii\base\Exception;

class CreateDraftHandler
{
    /**
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function create(
        ElementInterface $element,
        string $jobTitle,
        int $sourceSiteId,
        int $targetSiteId
    ): ElementInterface {

        /** Element will be created from original one, we can't create draft from draft */
        $createFrom = $element ? Craft::$app->elements->getElementById(
            $element->getCanonicalId()
        ) : $element;

        $draft = Craft::$app->drafts->createDraft(
            $createFrom,
            Craft::$app->user->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $jobTitle,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $sourceSiteId
                ),
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $targetSiteId
                )
            ),
            null
        );

        $draft = Craft::$app->elements->getElementById(
            $draft->getId(),
            'craft\elements\Entry',
            $targetSiteId
        );

        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            $field->copyValue($element, $draft);
        }
        /*
        if(
            get_class($field) === CraftliltpluginParameters::BENF_NEO_FIELD
            || get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX
            || get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE
        ) {
            $draft->setFieldValue($field->handle, $element->getFieldValue($field->handle));
        }
        */
        //Craft::$app->elements->saveElement($draft);

        $draft->title = $element->title;

        $draft->duplicateOf = $element;
        $draft->mergingCanonicalChanges = true;
        $draft->afterPropagate(false);

        Craft::$app->elements->saveElement($draft);

        return $draft;
    }
}
