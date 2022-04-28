<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;

class CreateJobHandler
{
    public function __invoke(CreateJob $command): void
    {
        $job = Craftliltplugin::getInstance()->liltJobRepository->create(
            $command->getTitle() . ' | {today}'
        );

        $files = [];

        foreach ($command->getEntries() as $entry) {
            $element = Craft::$app->elements->getElementById($entry);

            $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $element
            );

            $files[] = $this->createJobFile(
                $content,
                $entry,
                $job->getId(),
                $command->getTargetLanguages()
            );
        }

        Craftliltplugin::getInstance()->liltJobRepository->start($job->getId());

        //TODO: move to job repository

        $availableSites = Craft::$app->getSites()->getAllSites();
        $languageToId = [];
        $idToLanguage = [];
        foreach ($availableSites as $availableSite) {
            $languageToId[$availableSite->language] = $availableSite->id;
            $idToLanguage[$availableSite->id] = $availableSite->language;
        }

        $entry = new Job();
        $entry->title = $command->getTitle();
        $entry->liltJobId = $job->getId();
        $entry->status = 'new';
        $entry->sourceSiteId = $languageToId['en-US'];
        $entry->sourceSiteLanguage = 'en-US';
        $entry->targetSiteIds = $command->getTargetSitesIds();
        $entry->files = $files;
        $entry->dueDate = new DateTime('-7 days');

        $jobRecord = new \lilthq\craftliltplugin\records\JobRecord();
        $jobRecord->setAttributes($entry->getAttributes(), false);
        $jobRecord->save();
    }

    private function createJobFile(array $content, int $entryId, int $jobId, array $targetSiteLanguages): string
    {
        $file = $this->createTranslateFile(
            $jobId,
            $content
        );

        if (!file_exists($file)) {
            throw new \RuntimeException("File {$file} not exist!");
        }

        Craftliltplugin::getInstance()->liltJobsFileRepository->addFileToJob(
            $jobId,
            'entry_' . $entryId . '.json+html',
            file_get_contents($file),
            $targetSiteLanguages,
            (new DateTime())->setTimestamp(strtotime('+2 weeks'))
        );

        return $file;
    }

    private function createTranslateFile(int $id, array $content): string
    {
        $tempPath = Craft::$app->path->getTempPath();
        $fileName = sprintf(
            'lilt-translate-file-%d-%s.json',
            $id,
            date('Y-m-d')
        );

        $contentString = json_encode($content);

        file_put_contents($tempPath . '/' . $fileName, $contentString);

        return $tempPath . '/' . $fileName;
    }
}