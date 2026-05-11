<?php

declare(strict_types=1);

namespace App\Http\Requests\TicketTypePrice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Защита публичного getList:
 *  - filter.ticket_type_id обязателен (uuid + exists), иначе вернётся ВЕСЬ список волн
 *  - orderBy.* допускает только asc/desc (предотвращает InvalidArgumentException из OrderType)
 */
class TicketTypePriceGetListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['required', 'array'],
            'filter.ticket_type_id' => ['required', 'string', 'uuid', 'exists:ticket_type,id'],
            'orderBy' => ['sometimes', 'array'],
            'orderBy.*' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.required' => 'Не передан фильтр',
            'filter.ticket_type_id.required' => 'Не указан тип билета',
            'filter.ticket_type_id.uuid' => 'Тип билета должен быть UUID',
            'filter.ticket_type_id.exists' => 'Указанный тип билета не существует',
            'orderBy.*.in' => 'Направление сортировки должно быть asc или desc',
        ];
    }
}
