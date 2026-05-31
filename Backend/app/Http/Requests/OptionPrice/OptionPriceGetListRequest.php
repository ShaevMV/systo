<?php

declare(strict_types=1);

namespace App\Http\Requests\OptionPrice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Защита публичного getList волн цен опций:
 *  - filter.option_id обязателен (uuid + exists), иначе вернётся весь список.
 *  - orderBy.* — только asc/desc (предотвращает InvalidArgumentException).
 */
class OptionPriceGetListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['required', 'array'],
            'filter.option_id' => ['required', 'string', 'uuid', 'exists:options,id'],
            'orderBy' => ['sometimes', 'array'],
            'orderBy.*' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.required' => 'Не передан фильтр',
            'filter.option_id.required' => 'Не указана опция',
            'filter.option_id.uuid' => 'option_id должен быть UUID',
            'filter.option_id.exists' => 'Указанная опция не существует',
            'orderBy.*.in' => 'Направление сортировки должно быть asc или desc',
        ];
    }
}
