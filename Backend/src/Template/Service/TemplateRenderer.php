<?php

declare(strict_types=1);

namespace Tickets\Template\Service;

use Mustache\Engine;

/**
 * Рендер тела шаблона через Mustache (logic-less).
 *
 * БЕЗОПАСНОСТЬ (критично): Mustache не исполняет PHP — в шаблоне доступны только подстановка
 * {{ var }} (HTML-escape), секции/циклы {{#x}}…{{/x}}, инверсия {{^x}}…{{/x}} и raw {{{ x }}}.
 * Любой <?php … ?> выводится как ЭКРАНИРОВАННЫЙ текст. RCE из пользовательского шаблона
 * невозможен архитектурно (в отличие от Blade::render($userInput)). См. .claude/specs/template-system.md §2.
 */
class TemplateRenderer
{
    private Engine $mustache;

    public function __construct()
    {
        $this->mustache = new Engine([
            // ENT_QUOTES — экранируем и одинарные кавычки (безопасность в HTML-атрибутах).
            'escape' => static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context): string
    {
        return $this->mustache->render($template, $context);
    }
}
