<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string|null $email
 * @property string $date
 * @property string $id_buy
 * @property string $phone
 * @property string $city
 * @property string|null $comment
 * @property array $guests
 * @property string|null $promo_code
 * @property string $ticket_type_id
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
            'email' => 'required|email',
            'date' => 'required|before:now',
            'id_buy' => 'required|numeric',
            'phone' => 'required',
            'city' => 'required',
            'guests' => 'required|array',
            'ticket_type_id' => 'exists:App\Models\Ordering\InfoForOrder\TicketTypesModel,id',
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
            '*.before' => 'Дата платежа не может быть в будущем'
        ];
    }
}
