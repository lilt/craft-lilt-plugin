<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use craft\base\UtilityInterface;
use craft\errors\ElementNotFoundException;
use craft\web\assets\utilities\UtilitiesAsset;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\utilities\Configuration;
use lilthq\craftliltplugin\utilities\SystemReport;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\Response;

class GetSettingsFormController extends AbstractJobController
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionInvoke(string $id = 'lilt-configuration'): Response
    {
        foreach ($this->getAllUtilityTypes() as $class) {
            if ($class::id() === $id) {
                break;
            }
        }

        $this->getView()->registerAssetBundle(UtilitiesAsset::class);

        return $this->renderTemplate('craft-lilt-plugin/utilities.twig', [
            'id' => $id,
            'displayName' => $class::displayName(),
            'contentHtml' => $class::contentHtml(),
            'toolbarHtml' => $class::toolbarHtml(),
            'footerHtml' => $class::footerHtml(),
            'utilities' => $this->getAllUtilitiesInfo(),
        ]);
    }

    /**
     * @return UtilityInterface[]
     */
    private function getAllUtilityTypes(): array
    {
        return [
            SystemReport::class,
            Configuration::class,
        ];
    }

    private function getAllUtilitiesInfo(): array
    {
        $info = [];

        foreach ($this->getAllUtilityTypes() as $class) {
            $info[] = [
                'id' => $class::id(),
                'iconSvg' => file_get_contents($class::iconPath()),
                'displayName' => $class::displayName(),
                'iconPath' => $class::iconPath(),
                'badgeCount' => $class::badgeCount(),
            ];
        }

        return $info;
    }
}
