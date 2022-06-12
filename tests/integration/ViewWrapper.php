<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use craft\web\View;
use JsonException;

class ViewWrapper extends View
{
    /**
     * @var string|null
     */
    public $data = null;

    public $controllerView = null;

    public function setControllerView(View $view): self
    {
        $this->controllerView = $view;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function renderTemplate(string $template, array $variables = [], string $templateMode = null): string
    {
        $this->data = json_encode([
            'template' => $template,
            'variables' => $variables,
            'templateMode' => $templateMode
        ], 4194304);

        if($this->controllerView !== null) {
            return $this->controllerView->renderTemplate($template, $variables, $templateMode);
        }

        return $this->data;
    }
}