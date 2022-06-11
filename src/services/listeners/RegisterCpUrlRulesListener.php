<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use yii\base\Event;

class RegisterCpUrlRulesListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof RegisterUrlRulesEvent) {
            return $event;
        }

        $event->rules['POST ' . CraftliltpluginParameters::JOB_CREATE_PATH]
            = 'craft-lilt-plugin/job/post-create-job/invoke';
        $event->rules['GET craft-lilt-plugin/job/create']
            = 'craft-lilt-plugin/job/get-job-create-form/invoke';
        $event->rules['GET ' . CraftliltpluginParameters::JOB_EDIT_PATH . '/<jobId:\d+>']
            = 'craft-lilt-plugin/job/get-job-edit-form/invoke';
        $event->rules['POST ' . CraftliltpluginParameters::JOB_EDIT_PATH . '/<jobId:\d+>']
            = 'craft-lilt-plugin/job/post-edit-job/invoke';
        $event->rules['GET ' . CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH . '/<jobId:\d+>']
            = 'craft-lilt-plugin/job/get-send-to-lilt/invoke';
        $event->rules['GET ' . CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH . '/<jobId:\d+>']
            = 'craft-lilt-plugin/job/get-sync-from-lilt/invoke';
        $event->rules['GET craft-lilt-plugin']
            = 'craft-lilt-plugin/index/index';
        $event->rules['GET craft-lilt-plugin/jobs']
            = 'craft-lilt-plugin/jobs/index';
        $event->rules['GET craft-lilt-plugin/job/translation-review']
            = 'craft-lilt-plugin/job/get-translation-review/invoke';
        $event->rules['GET craft-lilt-plugin/settings/<id>']
            = 'craft-lilt-plugin/get-settings-form/invoke';
        $event->rules['craft-lilt-plugin/settings']
            = 'craft-lilt-plugin/get-settings-form/invoke';
        $event->rules['POST craft-lilt-plugin/settings/lilt-configuration']
            = 'craft-lilt-plugin/post-configuration/invoke';

        $event->rules['POST ' . CraftliltpluginParameters::JOB_POST_SYNC_PATH]
            = CraftliltpluginParameters::JOB_POST_SYNC_ACTION;

        $event->rules['POST craft-lilt-plugin/job/translation/publish']
            = CraftliltpluginParameters::TRANSLATION_PUBLISH_ACTION;

        $event->rules['POST craft-lilt-plugin/job/translation/review']
            = CraftliltpluginParameters::TRANSLATION_REVIEW_ACTION;

        return $event;
    }
}
