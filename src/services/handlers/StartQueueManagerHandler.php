<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\helpers\Queue as CraftHelpersQueue;
use lilthq\craftliltplugin\modules\QueueManager;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;

class StartQueueManagerHandler
{
    private const DEFAULT_TIME_TO_WAIT = 3600 * 5; // every 5 hours
    private const ENV_NAME = 'CRAFT_LILT_PLUGIN_QUEUE_MANAGER_WAIT_TIME_IN_SECONDS';

    private function isEligibleForRun(): bool
    {
        if (Craft::$app->request->isConsoleRequest && method_exists(Craft::$app->request, 'getParams')) {
            $params = Craft::$app->request->getParams();

            // Queue triggered from console
            return !empty($params[0]) && strpos($params[0], 'queue') !== false;
        }

        if (Craft::$app->request->isCpRequest) {
            $url = Craft::$app->request->getUrl();
            if (empty($url)) {
                return false;
            }

            // Plugin page triggered from web
            return strpos($url, 'craft-lilt-plugin') !== false;
        }

        return false;
    }

    public function handle(): void
    {
        if (!$this->isEligibleForRun()) {
            return;
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__;
        if (!$mutex->acquire($mutexKey)) {
            return;
        }

        $settingsTableSchema = Craft::$app->db->schema->getTableSchema(CraftliltpluginParameters::SETTINGS_TABLE_NAME);
        if ($settingsTableSchema === null) {
            return;
        }

        $queueManagerExecutedAt = SettingRecord::findOne(['name' => SettingsRepository::QUEUE_MANAGER_EXECUTED_AT]);

        if (empty($queueManagerExecutedAt) || empty($queueManagerExecutedAt->value)) {
            // no value, means first run

            $queueManagerExecutedAt = new SettingRecord(
                ['name' => SettingsRepository::QUEUE_MANAGER_EXECUTED_AT]
            );
            $queueManagerExecutedAt->value = time();
            $queueManagerExecutedAt->save();

            return;
        }

        $lastExecutedAt = (int)$queueManagerExecutedAt->value;

        $timeToWait = getenv(self::ENV_NAME);
        if (empty($timeToWait)) {
            $timeToWait = self::DEFAULT_TIME_TO_WAIT;
        }

        if ($lastExecutedAt < time() - (int) $timeToWait) {
            $queueManagerExecutedAt->value = time();
            $queueManagerExecutedAt->save();

            // we can push
            CraftHelpersQueue::push(
                new QueueManager(),
                SendJobToConnector::PRIORITY,
                0
            );
        }
    }
}
