<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\App;
use lilthq\craftliltplugin\Craftliltplugin;

class SystemReport extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('app', 'System Report');
    }

    public static function iconPath()
    {
        return Craft::getAlias('@appicons/check.svg');
    }

    public static function id(): string
    {
        return 'lilt-system-report';
    }

    public static function contentHtml(): string
    {
        $pluginRequirements = [
            [
                'name' => 'PHP 7.2+',
                'mandatory' => true,
                'condition' => true,
                'memo' => 'PHP 7.2 or later is required.',
                'error' => !version_compare(App::phpVersion(), '7.2', '>='),
                'warning' => false
            ],
            [
                'name' => 'Craft CMS 3.7.0+',
                'mandatory' => true,
                'condition' => true,
                'memo' => 'Craft CMS 3.7.0 or later is required',
                'error' => !version_compare(Craft::$app->getVersion(), '3.7.0', '>='),
                'warning' => false
            ],
        ];

        $allFieldTypes = Craft::$app->fields->getAllFieldTypes();
        $requirements = [];

        $fieldContentProvider = Craftliltplugin::getInstance()->fieldContentProvider;

        foreach ($allFieldTypes as $fieldType) {
            $hasProvider = isset($fieldContentProvider->providersMap[$fieldType]);

            $hasError = !$hasProvider
                && !isset($fieldContentProvider->fieldsTranslatableMap[$fieldType]);

            $isTranslatable = (
                isset($fieldContentProvider->fieldsTranslatableMap[$fieldType])
                && $fieldContentProvider->fieldsTranslatableMap[$fieldType]['translatable']
            );

            $isSupported = $hasProvider || $isTranslatable;

            $fieldSupport = [
                'name' => $fieldType,
                'mandatory' => true,
                'condition' => true,
                'memo' => null,
                'error' => $hasError,
                'warning' => !$isSupported
            ];

            if (!$hasProvider && !$hasError && !$isTranslatable) {
                $fieldSupport['memo'] = sprintf('%s is not translatable', $fieldType);
            }

            if ($hasError) {
                $fieldSupport['memo'] = sprintf('%s is not supported', $fieldType);
            }

            $requirements[] = $fieldSupport;
        }

        return Craft::$app->getView()->renderTemplate('craft-lilt-plugin/_components/utilities/system-report.twig', [
            'appInfo' => null,
            'plugins' => [],
            'modules' => [],
            'aliases' => [],
            'requirements' => $requirements,
            'pluginRequirements' => $pluginRequirements,
        ]);
    }
}
