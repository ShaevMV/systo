<?php

declare(strict_types=1);

namespace App\Http\Requests\TicketTypePrice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Защита от дурака на создание волны цены билета.
 *
 * Правила:
 *  - price > 0 и < 1 000 000 (предохранитель от случайных миллиардов)
 *  - before_date — валидная дата, не в прошлом (нельзя задним числом)
 *  - ticket_type_id — существующий тип билета
 */
class TicketTypePriceCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.ticket_type_id' => ['required', 'string', 'uuid', 'exists:ticket_type,id'],
            'data.price' => ['required', 'numeric', 'gt:0', 'lt:1000000'],
            'data.before_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.ticket_type_id.required' => 'Не указан тип билета',
            'data.ticket_type_id.uuid' => 'Тип билета должен быть UUID',
            'data.ticket_type_id.exists' => 'Указанный тип билета не существует',
            'data.price.required' => 'Цена обязательна',
            'data.price.numeric' => 'Цена должна быть числом',
            'data.price.gt' => 'Цена должна быть больше 0',
            'data.price.lt' => 'Цена слишком велика (максимум 999 999)',
            'data.before_date.required' => 'Дата окончания волны обязательна',
            'data.before_date.date' => 'Некорректная дата',
            'data.before_date.after_or_equal' => 'Дата не может быть в прошлом',
        ];
    }
}
