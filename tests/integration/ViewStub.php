<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use craft\web\View;
use JsonException;

class ViewStub extends View
{
    /**
     * @throws JsonException
     */
    public function renderTemplate(string $template, array $variables = [], string $templateMode = null): string
    {
        return json_encode([
            'template' => $template,
            'variables' => $variables,
            'templateMode' => $templateMode
        ], JSON_THROW_ON_ERROR);
    }
}