<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Specification;

use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\PromoCode\Response\PromoCodeDto;

/**
 * Спецификация: промокод валиден и не превышает лимит использований.
 *
 * Логика перенесена из IsCorrectPromoCode::findPromoCode() +
 * LimitPromoCodeDto::getCorrect().
 *
 * Контекст (array $context):
 * - 'promoCode' => PromoCodeDto  (результат поиска промокода)
 * - 'promoCodeName' => string    (введённый пользователем код)
 */
final class PromoCodeSpecification implements OrderSpecificationInterface
{
    private array $errors = [];

    public function isSatisfiedBy(array $context): bool
    {
        $this->errors = [];

        $promoCodeName = $context['promoCodeName'] ?? null;

        if ($promoCodeName === null) {
            return true;
        }

        /** @var PromoCodeDto|null $promoCode */
        $promoCode = $context['promoCode'] ?? null;

        if ($promoCode === null || !$promoCode->isSuccess()) {
            $this->errors['promo_code'] = ['Промокод не найден или неактивен'];
            return false;
        }

        if (!$promoCode->getLimit()->getCorrect()) {
            $this->errors['promo_code'] = ['Лимит использований промокода исчерпан'];
            return false;
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
