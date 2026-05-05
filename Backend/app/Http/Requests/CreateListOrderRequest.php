<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $email
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $name
 * @property string|null $comment
 * @property string|null $project
 * @property string $festival_id
 * @property string $location_id
 * @property array $guests
 * @property array|null $autos
 */
class CreateListOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'festival_id' => 'required|exists:App\Models\Festival\FestivalModel,id',
            'location_id' => 'required|exists:App\Models\Location\LocationModel,id',
            'guests' => 'required|array|min:1',
            'guests.*.value' => 'required|string',
            'project' => 'nullable|string|max:255',
            'autos' => 'nullable|array',
            'autos.*' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Поле обязательно для ввода',
            'email.email' => 'Поле должно быть email',
            '*.exists' => 'Такой записи нет в системе',
            'guests.array' => 'Гости должны быть массивом',
            'guests.min'   => 'Должен быть указан хотя бы один гость',
        ];
    }
}
