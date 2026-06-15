<?php

declare(strict_types=1);

namespace Tickets\Template\Domain;

/**
 * Контракт переменных шаблонов: тестовые данные (sample) для предпросмотра БЕЗ ПДн реальных заказов.
 *
 * Preview-эндпоинт рендерит шаблон именно на этих фикстурах — нельзя утечь данные реального заказа.
 * Slug передаётся для будущей специализации набора под конкретное письмо/билет (пока общий набор).
 */
final class PlaceholderCatalog
{
    /** Крошечный валидный PNG 1×1 как data-URI — заглушка под QR-код в превью. */
    private const SAMPLE_QR = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * Демо-данные для предпросмотра по типу шаблона. Покрывают объединение переменных,
     * используемых текущими письмами и PDF (festivalName/comment/promocode/guests/tickets/…).
     *
     * @return array<string, mixed>
     */
    public static function sample(string $kind, string $slug = ''): array
    {
        return [
            'festivalName' => 'Солар Систо 2026',
            'festivalShortName' => 'Систо',
            'locationName' => 'Сцена «Лес»',
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'kilter' => 1234,
            'year' => date('Y'),
            'comment' => 'Пример комментария к заказу',
            'promocode' => 'SUN10',
            'url' => self::SAMPLE_QR,
            'questionnaireLinks' => [
                ['name' => 'Иван Иванов', 'url' => 'https://org.spaceofjoy.ru/questionnaire/demo-order/demo-ticket'],
            ],
            'changes' => [
                ['oldName' => 'Пётр Сидоров', 'newName' => 'Павел Сидоров'],
            ],
            'guests' => [
                ['name' => 'Иван Иванов', 'email' => 'ivan@example.com', 'number' => 1],
                ['name' => 'Мария Петрова', 'email' => 'maria@example.com', 'number' => 2],
            ],
            'tickets' => [
                ['name' => 'Иван Иванов', 'kilter' => 1234],
                ['name' => 'Мария Петрова', 'kilter' => 1235],
            ],
        ];
    }

    /**
     * Палитра плейсхолдеров для редактора (вставка кликом). Группы → элементы со строкой вставки.
     * Slug — задел под per-письмо фильтрацию (пока общий набор по kind).
     *
     * @return array<int, array{group: string, items: array<int, array{label: string, insert: string}>}>
     */
    public static function variables(string $kind, string $slug = ''): array
    {
        $groups = [
            ['group' => 'Заказ', 'items' => [
                ['label' => 'Название фестиваля', 'insert' => '{{ festivalName }}'],
                ['label' => 'Комментарий', 'insert' => '{{ comment }}'],
                ['label' => 'Промокод (секция)', 'insert' => '{{#promocode}} промокод {{ promocode }} {{/promocode}}'],
            ]],
            ['group' => 'Гость', 'items' => [
                ['label' => 'Имя', 'insert' => '{{ name }}'],
                ['label' => 'Email', 'insert' => '{{ email }}'],
                ['label' => 'Номер билета', 'insert' => '{{ kilter }}'],
            ]],
            ['group' => 'Циклы', 'items' => [
                ['label' => 'По гостям', 'insert' => "{{#guests}}\n  {{ name }} — {{ email }}\n{{/guests}}"],
                ['label' => 'По билетам', 'insert' => "{{#tickets}}\n  {{ name }}\n{{/tickets}}"],
            ]],
        ];

        if ($kind === TemplateKind::PDF) {
            $groups[] = ['group' => 'PDF', 'items' => [
                ['label' => 'QR-код (raw, без экранирования)', 'insert' => '{{{ url }}}'],
            ]];
        } else {
            $groups[] = ['group' => 'Письмо', 'items' => [
                ['label' => 'Локация', 'insert' => '{{ locationName }}'],
                ['label' => 'Ссылки на анкеты (секция)', 'insert' => "{{#questionnaireLinks}}\n  {{ name }}: {{ url }}\n{{/questionnaireLinks}}"],
            ]];
        }

        return $groups;
    }
}
