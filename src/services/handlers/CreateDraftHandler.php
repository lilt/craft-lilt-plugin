<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
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
        /**
         * Element will be created from original one, we can't create draft from draft
         * @var Entry $createFrom
         */
        $createFrom = $element ? Craft::$app->elements->getElementById(
            $element->getCanonicalId()
        ) : $element;

        $creatorId = Craft::$app->user->getId();
        if ($creatorId === null) {
            //TODO: it is not expected to reach, but it is possible. Investigation herer, why user id is null?
            Craft::error(
                "Can't get user from current session with Craft::\$app->user->getId(),"
                . "please check you app configuration!"
            );
            $creatorId = $createFrom->authorId;
        }

        $draft = Craft::$app->drafts->createDraft(
            $createFrom,
            $creatorId ?? 0, //TODO: not best but one of the ways. Need to check why user can have nullable id?
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
        $fields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

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

        /**
         * TODO: for some reason we have duplicate error here.
         *  Craft tries to create same block. Need investigation here.
         */
        $draftId = $draft->draftId;
        $draft->draftId = null;
        $draft->afterPropagate(false);
        $draft->draftId = $draftId;

        Craft::$app->elements->saveElement($draft);

        return $draft;
    }
}
