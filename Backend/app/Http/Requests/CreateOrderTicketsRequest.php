<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Tickets\Shared\Domain\ValueObject\Uuid;


/**
 * @property string|null $email
 * @property string $date
 * @property string|null $guests
 * @property string|null $promoCode
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
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
