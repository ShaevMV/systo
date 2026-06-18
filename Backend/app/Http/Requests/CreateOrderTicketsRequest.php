<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос создания заказа (формат v2.6.0 — per-guest).
 *
 * Каждый элемент `guests[]` несёт свой `ticket_type_id`, `options[]`, `promo_code`.
 * Глубокая валидация одного гостя — в {@see \Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestInput::fromState()}
 * (граница Application-слоя), поэтому здесь правила намеренно лёгкие: общий контракт заказа.
 *
 * Этот же FormRequest используется в `createFriendly` (пушер задаёт цену вручную, тип билета —
 * на уровне заказа), поэтому per-guest поля не делаем `required` на уровне FormRequest.
 *
 * @property string|null $email
 * @property string $festival_id
 * @property string $phone
 * @property string|null $name
 * @property string $city
 * @property string $invite
 * @property string $price
 * @property string|null $comment
 * @property array $guests
 * @property string $types_of_payment_id
 */
class CreateOrderTicketsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // id заказа можно задать с клиента (внешняя система qr и org должны иметь
            // ОДИНАКОВЫЙ id заказа); не передан → генерируется на сервере (OrderTicketDto::fromState).
            'id' => 'sometimes|uuid',
            'email' => 'required|email',
            'phone' => 'required',
            'city' => 'required',
            'festival_id' => 'required',
            'guests' => 'required|array|min:1',
            'types_of_payment_id' => 'exists:App\Models\Ordering\InfoForOrder\TypesOfPaymentModel,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Поле обязательно для ввода',
            '*.email' => 'Поле должно быть email',
            '*.numeric' => 'Поле должно быть числом',
            '*.exists' => 'Такой записи нет системе',
            '*.before' => 'Дата платежа не может быть в будущем',
            'guests.required' => 'Не передан ни один гость',
            'guests.min' => 'В заказе должен быть хотя бы один гость',
        ];
    }
}
