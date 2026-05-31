<?php

declare(strict_types=1);

namespace App\Http\Requests\OptionPrice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Защита от дурака на редактирование волны цены опции.
 */
class OptionPriceEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.option_id' => ['required', 'string', 'uuid', 'exists:options,id'],
            'data.price' => ['required', 'integer', 'gt:0', 'lt:1000000'],
            'data.before_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.option_id.required' => 'Не указана опция',
            'data.option_id.uuid' => 'option_id должен быть UUID',
            'data.option_id.exists' => 'Указанная опция не существует',
            'data.price.required' => 'Цена обязательна',
            'data.price.integer' => 'Цена должна быть целым числом (рубли)',
            'data.price.gt' => 'Цена должна быть больше 0',
            'data.price.lt' => 'Цена слишком велика (максимум 999 999)',
            'data.before_date.required' => 'Дата окончания волны обязательна',
            'data.before_date.date' => 'Некорректная дата',
            'data.before_date.after_or_equal' => 'Дата не может быть в прошлом',
        ];
    }
}
