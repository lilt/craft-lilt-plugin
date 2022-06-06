<?php

declare(strict_types=1);

namespace lilthq\tests\fixtures;

use craft\test\fixtures\elements\EntryFixture;

class EntriesFixture extends EntryFixture
{
    public $dataFile = __DIR__ . '/data/entries.php';

    /**
     * @inheritdoc
     */
    public $depends = [
        FieldsFixture::class
    ];
}