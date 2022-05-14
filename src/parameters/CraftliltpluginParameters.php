<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\parameters;

class CraftliltpluginParameters
{
    public const JOB_EDIT_PATH = 'craft-lilt-plugin/job/edit';
    public const JOB_CREATE_PATH = 'craft-lilt-plugin/job/create';
    public const JOB_SEND_TO_LILT_PATH = 'craft-lilt-plugin/job/send-to-lilt';
    public const JOB_SYNC_FROM_LILT_PATH = 'craft-lilt-plugin/job/sync-from-lilt';

    public const JOB_TABLE_NAME = '{{%lilt_jobs}}';
    public const TRANSLATION_TABLE_NAME = '{{%lilt_translations}}';
    public const I18N_TABLE_NAME = '{{%lilt_i18n}}';
}
