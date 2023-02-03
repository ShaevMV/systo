<?php

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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
