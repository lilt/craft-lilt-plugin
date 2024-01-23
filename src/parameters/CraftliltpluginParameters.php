<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\parameters;

use Craft;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\services\listeners\AfterDraftAppliedListener;
use lilthq\craftliltplugin\services\listeners\AfterErrorListener;
use lilthq\craftliltplugin\services\listeners\QueueBeforePushListener;
use lilthq\craftliltplugin\services\listeners\RegisterCpAlertsListener;
use lilthq\craftliltplugin\services\listeners\RegisterDefaultTableAttributesListener;
use lilthq\craftliltplugin\services\listeners\RegisterElementActionsListener;
use lilthq\craftliltplugin\services\listeners\RegisterElementTypesListener;
use lilthq\craftliltplugin\services\listeners\RegisterTableAttributesListener;
use lilthq\craftliltplugin\services\listeners\RegisterCpUrlRulesListener;

class CraftliltpluginParameters
{
    public const REPORT_DATA = 'craft-lilt-plugin/get-report-data/invoke';
    public const TRANSLATION_REVIEW_ACTION = 'craft-lilt-plugin/translation/post-translation-review/invoke';
    public const TRANSLATION_PUBLISH_ACTION = 'craft-lilt-plugin/translation/post-translation-publish/invoke';
    public const TRANSLATION_REVIEW_PATH = 'craft-lilt-plugin/job/translation/review';
    public const JOB_POST_SYNC_ACTION = 'craft-lilt-plugin/job/post-sync/invoke';

    public const JOB_POST_RETRY_ACTION = 'craft-lilt-plugin/job/post-job-retry/invoke';
    public const JOB_POST_RETRY_PATH = 'craft-lilt-plugin/job/retry';

    public const JOB_EDIT_ACTION = 'craft-lilt-plugin/job/get-job-edit-form/invoke';
    public const JOB_EDIT_PATH = 'craft-lilt-plugin/job/edit';
    public const JOB_CREATE_PATH = 'craft-lilt-plugin/job/create';
    public const JOB_SEND_TO_LILT_PATH = 'craft-lilt-plugin/job/send-to-lilt';
    public const JOB_SYNC_FROM_LILT_PATH = 'craft-lilt-plugin/job/sync-from-lilt';
    public const JOB_POST_SYNC_PATH = 'craft-lilt-plugin/job/sync';
    public const POST_CONFIGURATION_PATH = 'craft-lilt-plugin/settings/lilt-configuration';

    public const JOB_TABLE_NAME = '{{%lilt_jobs}}';
    public const TRANSLATION_TABLE_NAME = '{{%lilt_translations}}';
    public const TRANSLATION_NOTIFICATIONS_TABLE_NAME = '{{%lilt_translations_notifications}}';
    public const I18N_TABLE_NAME = '{{%lilt_i18n}}';
    public const SETTINGS_TABLE_NAME = '{{%lilt_settings}}';
    public const JOB_LOGS_TABLE_NAME = '{{%lilt_jobs_logs}}';

    public const CRAFT_FIELDS_MATRIX = 'craft\fields\Matrix';
    public const CRAFT_FIELDS_PLAINTEXT = 'craft\fields\PlainText';
    public const CRAFT_FIELDS_LIGHTSWITCH = 'craft\fields\Lightswitch';
    public const CRAFT_REDACTOR_FIELD = 'craft\redactor\Field';
    public const CRAFT_FIELDS_TABLE = 'craft\fields\Table';
    public const CRAFT_FIELDS_SUPER_TABLE = 'verbb\supertable\fields\SuperTableField';
    public const CRAFT_FIELDS_RADIOBUTTONS = 'craft\fields\RadioButtons';
    public const CRAFT_FIELDS_DROPDOWN = 'craft\fields\Dropdown';
    public const CRAFT_FIELDS_MULTISELECT = 'craft\fields\MultiSelect';
    public const CRAFT_FIELDS_CHECKBOXES = 'craft\fields\Checkboxes';
    public const CRAFT_FIELDS_BASEOPTIONSFIELD = 'craft\fields\BaseOptionsField';
    public const BENF_NEO_FIELD = 'benf\neo\Field';
    public const LINKIT_FIELD = 'fruitstudios\linkit\fields\LinkitField';
    public const COLOUR_SWATCHES_FIELD = 'percipioglobal\colourswatches\fields\ColourSwatches';

    public const LENZ_LINKFIELD = 'lenz\linkfield\fields\LinkField';

    public const LISTENERS = [
        AfterDraftAppliedListener::class,
        RegisterCpUrlRulesListener::class,
        RegisterElementTypesListener::class,
        RegisterDefaultTableAttributesListener::class,
        RegisterTableAttributesListener::class,
        AfterErrorListener::class,
        RegisterCpAlertsListener::class,
        RegisterElementActionsListener::class,
        QueueBeforePushListener::class,
    ];

    public const TRANSLATION_WORKFLOW_INSTANT = SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT;
    public const TRANSLATION_WORKFLOW_VERIFIED = SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED;
    public const TRANSLATION_WORKFLOW_COPY_SOURCE_TEXT = 'COPY_SOURCE_TEXT';

    public const DEFAULT_CACHE_RESPONSE_IN_SECONDS = 60;
    public static function getTranslationWorkflows(): array
    {
        return [
            strtolower(self::TRANSLATION_WORKFLOW_INSTANT) =>
                Craft::t(
                    'craft-lilt-plugin',
                    strtolower(self::TRANSLATION_WORKFLOW_INSTANT)
                ),
            strtolower(self::TRANSLATION_WORKFLOW_VERIFIED) =>
                Craft::t(
                    'craft-lilt-plugin',
                    strtolower(self::TRANSLATION_WORKFLOW_VERIFIED)
                ),
            strtolower(self::TRANSLATION_WORKFLOW_COPY_SOURCE_TEXT) =>
                Craft::t(
                    'craft-lilt-plugin',
                    strtolower(self::TRANSLATION_WORKFLOW_COPY_SOURCE_TEXT)
                )
        ];
    }

    public static function getResponseCache(): int
    {
        $envCache = getenv('CRAFT_LILT_PLUGIN_CACHE_RESPONSE_IN_SECONDS');
        $cache =
            !empty($envCache) || $envCache === '0' ?
                (int)$envCache :
                CraftliltpluginParameters::DEFAULT_CACHE_RESPONSE_IN_SECONDS;

        return $cache;
    }
}
