<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\i18n;

use craft\i18n\PhpMessageSource as CraftPhpMessageSource;
use lilthq\craftliltplugin\Craftliltplugin;

class PhpMessageSource extends CraftPhpMessageSource
{
    /**
     * @inheritdoc
     */
    protected function loadMessages($category, $language): array
    {
        $messages = [];

        $i18NRecords = Craftliltplugin::getInstance()->i18NRepository->findAllByTargetSiteId(
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($language)
        );

        foreach ($i18NRecords as $i18NRecord) {
            $messages[$i18NRecord->source] = $i18NRecord->target;
        }

        return $messages;
    }
}