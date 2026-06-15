<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Service\TemplateRenderer;

/**
 * Трейт для Mailable: рендер тела письма из активного DB-шаблона (Mustache) с fallback на blade.
 *
 * Пока активной записи templates по (slug, email) нет — письмо рендерится старым blade-view
 * (поведение без изменений). Активация шаблона из админки переключает рендер на БД БЕЗ деплоя.
 * Резолвер/рендерер берём из контейнера (Mailable создаётся в очереди).
 */
trait RendersDbTemplate
{
    /**
     * Установить тело письма: из активного DB-шаблона или fallback на blade `email.{slug}`.
     * Возвращает $this (как $this->view()/$this->html()) — можно продолжать attachData() и т.п.
     *
     * @param array<string, mixed> $vars
     */
    protected function renderDbOrView(string $slug, array $vars): static
    {
        // Общие переменные для всех писем (в Mustache доступны как {{ year }}).
        // В blade-шаблоне год берётся через date('Y'), поэтому для fallback безвредно
        // (лишнюю переменную blade просто игнорирует). Mustache-шаблоны используют {{ year }}.
        $vars = array_merge(['year' => (int) date('Y')], $vars);

        $template = app(TemplateRepositoryInterface::class)->findActive($slug, TemplateKind::EMAIL);

        if ($template !== null) {
            return $this->html(app(TemplateRenderer::class)->render($template->getRenderBody(), $vars));
        }

        return $this->view('email.' . $slug, $vars);
    }
}
