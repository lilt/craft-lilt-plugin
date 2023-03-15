<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\events\RegisterCpAlertsEvent;
use craft\helpers\Cp;
use lilthq\craftliltplugin\Craftliltplugin;
use yii\base\Event;

class RegisterCpAlertsListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_ALERTS,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof RegisterCpAlertsEvent) {
            return $event;
        }

        if (empty(Craft::$app->request)) {
            // There is no request, looks like it is not web interface
            return $event;
        }

        $url = Craft::$app->request->getUrl();
        if (empty($url)) {
            // There is no url in request, looks like it is not web interface
            return $event;
        }

        if (strpos($url, 'craft-lilt-plugin') === false) {
            // User not on plugin page, we can't show alert
            return $event;
        }

        $latestVersion = Craft::$app->cache->get('craftliltplugin-latest-version');
        $currentVersion = Craftliltplugin::getInstance()->getVersion();

        if ($latestVersion === false) {
            $latestVersion = Craftliltplugin::getInstance()->packagistRepository->getLatestPluginVersion();

            Craft::$app->cache->add(
                'craftliltplugin-latest-version',
                $latestVersion,
                3600
            );
        }

        if (empty($latestVersion)) {
            return $event;
        }

        if (version_compare($latestVersion, $currentVersion, '>')) {
            $event->alerts[] = sprintf('The Lilt plugin is outdated. Please update to version %s', $latestVersion);
        }

        return $event;
    }
}
