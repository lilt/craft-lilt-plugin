<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use Craft;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\records\JobLogRecord;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;
use yii\web\Response;
use craft\helpers\Json;

class GetReportDataController extends AbstractJobController
{
    protected int|bool|array $allowAnonymous = false;

    private $savePath;

    /**
     * @throws Throwable
     * TODO: move to handler/command
     */
    public function actionInvoke(?int $jobId = null): Response
    {
        // Path to the directory where you want to save the downloaded files
        $this->savePath = sprintf(
            '%s%s%s%s%s%s',
            Craft::$app->getPath()->getTempPath(),
            DIRECTORY_SEPARATOR,
            'craft-lilt-plugin',
            DIRECTORY_SEPARATOR,
            uniqid(),
            DIRECTORY_SEPARATOR
        );

        if (!file_exists($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }

        try {
            // Copy composer.json
            $composerJsonPath = CRAFT_BASE_PATH . '/composer.json';
            $composerJsonContents = file_get_contents($composerJsonPath);
            file_put_contents($this->savePath . 'composer.json', $composerJsonContents);

            // Copy composer.lock
            $composerLockPath = CRAFT_BASE_PATH . '/composer.lock';
            $composerLockContents = file_get_contents($composerLockPath);
            file_put_contents($this->savePath . 'composer.lock', $composerLockContents);

            // Copy logs
            $logsPath = Craft::$app->getPath()->getLogPath();
            $logFiles = glob($logsPath . '/*.log');

            $logsFolderPath = $this->savePath . 'logs' . DIRECTORY_SEPARATOR;
            if (!file_exists($logsFolderPath)) {
                mkdir($logsFolderPath);
            }
            foreach ($logFiles as $logFile) {
                $filename = basename($logFile);
                copy($logFile, $logsFolderPath . $filename);
            }

            // Copy configuration YAML files
            $configPath = Craft::$app->getPath()->getConfigPath();
            $yamlFiles = glob($configPath . '/*.yaml');

            $configFolderPath = $this->savePath . 'config' . DIRECTORY_SEPARATOR;
            if (!file_exists($configFolderPath)) {
                mkdir($configFolderPath);
            }
            foreach ($yamlFiles as $yamlFile) {
                $filename = basename($yamlFile);
                copy($yamlFile, $configFolderPath . $filename);
            }

            if (!empty($jobId)) {
                $this->saveRecords(JobRecord::class, ['id' => $jobId]);
                $this->saveRecords(TranslationRecord::class, ['jobId' => $jobId]);
                $this->saveRecords(JobLogRecord::class, ['jobId' => $jobId]);
            }

            // Create a ZIP archive
            $zipPath = sprintf(
                '%s%s - %s.zip',
                $this->savePath,
                Craft::$app->sites->currentSite->name,
                date('Y-m-d H:i:s')
            );

            $zip = new \PharData($zipPath);

            $zip->buildFromDirectory($this->savePath);

            // Send the ZIP file for download
            return Craft::$app->getResponse()->sendFile($zipPath, null, ['forceDownload' => true]);
        } catch (\Exception $e) {
            // Handle any exceptions that occur during the process
            $error = sprintf('An error occurred while downloading the files: %s', $e->getMessage());
            Craft::error($error, __METHOD__);
            die($error);
        }
    }

    /**
     * @param string $recordClass
     * @param array $criteria
     * @return bool
     */
    public function saveRecords(string $recordClass, array $criteria = []): bool
    {
        if (!method_exists($recordClass, 'findAll') || !method_exists($recordClass, 'tableName')) {
            return false;
        }

        $records = $recordClass::findAll($criteria);

        $data = [];
        foreach ($records as $record) {
            $data[] = $record->toArray();
        }

        $jsonData = Json::encode($data);

        $filePath = $this->savePath . $recordClass::tableName() . '.json';
        return file_put_contents($filePath, $jsonData) !== false;
    }
}
