<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
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
    public function create(ElementInterface $element, string $jobTitle, int $sourceSiteId, int $targetLanguage): ElementInterface
    {
        $draft = Craft::$app->drafts->createDraft(
            $element,
            Craft::$app->user->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $jobTitle,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $sourceSiteId
                ),
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $targetLanguage
                )
            ),
            null
        );

        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            $field->copyValue($element, $draft);
        }
        Craft::$app->elements->saveElement($draft);

        $draft->mergingCanonicalChanges = true;
        $draft->afterPropagate(false);

        return $draft;
    }
}
