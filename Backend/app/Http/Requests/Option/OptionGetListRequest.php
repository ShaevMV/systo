<?php

declare(strict_types=1);

namespace App\Http\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Защита публичного getList опций:
 *  - filter.* — все опциональны (страница админки может листать всё)
 *  - orderBy.* — только asc/desc
 */
class OptionGetListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.name' => ['sometimes', 'nullable', 'string'],
            'filter.festival_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'filter.active' => ['sometimes', 'nullable', 'boolean'],
            'orderBy' => ['sometimes', 'array'],
            'orderBy.*' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.festival_id.uuid' => 'festival_id должен быть UUID',
            'orderBy.*.in' => 'Направление сортировки должно быть asc или desc',
        ];
    }
}
