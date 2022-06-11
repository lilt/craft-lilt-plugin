<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\parameters;

use lilthq\craftliltplugin\services\listeners\AfterDraftAppliedListener;
use lilthq\craftliltplugin\services\listeners\RegisterDefaultTableAttributesListener;
use lilthq\craftliltplugin\services\listeners\RegisterElementTypesListener;
use lilthq\craftliltplugin\services\listeners\RegisterTableAttributesListener;
use lilthq\craftliltplugin\services\listeners\RegisterCpUrlRulesListener;

class CraftliltpluginParameters
{
    public const TRANSLATION_REVIEW_ACTION = 'craft-lilt-plugin/translation/post-translation-review/invoke';
    public const TRANSLATION_PUBLISH_ACTION = 'craft-lilt-plugin/translation/post-translation-publish/invoke';
    public const TRANSLATION_REVIEW_PATH = 'craft-lilt-plugin/job/translation/review';
    public const JOB_POST_SYNC_ACTION = 'craft-lilt-plugin/job/post-sync/invoke';

    public const JOB_EDIT_ACTION = 'craft-lilt-plugin/job/get-job-edit-form/invoke';
    public const JOB_EDIT_PATH = 'craft-lilt-plugin/job/edit';
    public const JOB_CREATE_PATH = 'craft-lilt-plugin/job/create';
    public const JOB_SEND_TO_LILT_PATH = 'craft-lilt-plugin/job/send-to-lilt';
    public const JOB_SYNC_FROM_LILT_PATH = 'craft-lilt-plugin/job/sync-from-lilt';
    public const JOB_POST_SYNC_PATH = 'craft-lilt-plugin/job/sync';

    public const JOB_TABLE_NAME = '{{%lilt_jobs}}';
    public const TRANSLATION_TABLE_NAME = '{{%lilt_translations}}';
    public const I18N_TABLE_NAME = '{{%lilt_i18n}}';
    public const SETTINGS_TABLE_NAME = '{{%lilt_settings}}';
    public const JOB_LOGS_TABLE_NAME = '{{%lilt_jobs_logs}}';

    public const CRAFT_FIELDS_MATRIX            = 'craft\fields\Matrix';
    public const CRAFT_FIELDS_PLAINTEXT         = 'craft\fields\PlainText';
    public const CRAFT_FIELDS_LIGHTSWITCH       = 'craft\fields\Lightswitch';
    public const CRAFT_REDACTOR_FIELD           = 'craft\redactor\Field';
    public const CRAFT_FIELDS_TABLE             = 'craft\fields\Table';
    public const CRAFT_FIELDS_SUPER_TABLE       = 'verbb\supertable\fields\SuperTableField';
    public const CRAFT_FIELDS_RADIOBUTTONS      = 'craft\fields\RadioButtons';
    public const CRAFT_FIELDS_DROPDOWN          = 'craft\fields\Dropdown';
    public const CRAFT_FIELDS_MULTISELECT       = 'craft\fields\MultiSelect';
    public const CRAFT_FIELDS_CHECKBOXES        = 'craft\fields\Checkboxes';
    public const CRAFT_FIELDS_BASEOPTIONSFIELD  = 'craft\fields\BaseOptionsField';
    public const BENF_NEO_FIELD                 = 'benf\neo\Field';

    public const LISTENERS = [
        AfterDraftAppliedListener::class,
        RegisterCpUrlRulesListener::class,
        RegisterElementTypesListener::class,
        RegisterDefaultTableAttributesListener::class,
        RegisterTableAttributesListener::class,
    ];
}
