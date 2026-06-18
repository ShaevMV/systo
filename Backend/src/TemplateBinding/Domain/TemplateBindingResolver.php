<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Domain;

use Tickets\TemplateBinding\Dto\TemplateBindingDto;

/**
 * Чистая логика выбора привязки шаблона — БЕЗ БД (юнит-тестируемо).
 *
 * Алгоритм:
 *  1. Из активных привязок берём подходящие под (event, festival, order_type, ticket_type) — где
 *     каждое из полей либо совпадает, либо NULL (wildcard) — и у которых есть slug для нужного kind.
 *  2. Выбираем САМУЮ специфичную (event > ticket_type > order_type > festival, см. specificity()).
 *  3. Нет совпадения → is_default-привязка со slug для kind.
 *  4. Нет дефолта → null (вызывающий уходит на старый slug из ticket_type_festival).
 *
 * event = null в запросе → подходят только привязки с event = null (обратная совместимость
 * для резолва PDF/выдачи, который событие не передаёт).
 */
class TemplateBindingResolver
{
    /**
     * @param TemplateBindingDto[] $bindings активные привязки (со slug'ами из join templates)
     * @param string|null $event код события письма (EmailEvent) или null = «любое»
     * @return string|null slug привязанного шаблона или null (→ старое поведение/blade-fallback)
     */
    public function resolve(
        array $bindings,
        string $kind,
        ?string $event,
        ?string $festivalId,
        ?string $orderType,
        ?string $ticketTypeId,
        ?string $typesOfPaymentId = null,
    ): ?string {
        $candidates = [];
        foreach ($bindings as $binding) {
            if (! $binding->isActive() || $binding->slugForKind($kind) === null || $binding->isDefault()) {
                continue;
            }
            if ($binding->matches($event, $festivalId, $orderType, $ticketTypeId, $typesOfPaymentId)) {
                $candidates[] = $binding;
            }
        }

        if ($candidates !== []) {
            usort(
                $candidates,
                static fn (TemplateBindingDto $a, TemplateBindingDto $b): int => $b->specificity() <=> $a->specificity(),
            );

            return $candidates[0]->slugForKind($kind);
        }

        // Шаг 3 — дефолт-fallback.
        foreach ($bindings as $binding) {
            if ($binding->isActive() && $binding->isDefault() && $binding->slugForKind($kind) !== null) {
                return $binding->slugForKind($kind);
            }
        }

        // Шаг 4 — нет привязки: вызывающий использует старый slug.
        return null;
    }
}
