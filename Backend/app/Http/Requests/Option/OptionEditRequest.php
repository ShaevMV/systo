<?php

declare(strict_types=1);

namespace App\Http\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Защита от дурака на редактирование опции.
 *
 * `bindings` опциональный — если не передан, привязки не трогаются.
 * Чтобы очистить все привязки — передать `bindings: []`.
 */
class OptionEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.name' => ['required', 'string', 'max:255'],
            'data.active' => ['sometimes', 'boolean'],
            'data.festival_id' => ['required', 'string', 'uuid', 'exists:festivals,id'],

            'data.bindings' => ['sometimes', 'array'],
            'data.bindings.*.ticket_type_id' => ['required_with:data.bindings', 'string', 'uuid', 'exists:ticket_type,id'],
            'data.bindings.*.description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.name.required' => 'Не указано название опции',
            'data.name.max' => 'Название слишком длинное (макс. 255)',
            'data.festival_id.required' => 'Не указан фестиваль',
            'data.festival_id.uuid' => 'festival_id должен быть UUID',
            'data.festival_id.exists' => 'Указанный фестиваль не существует',
            'data.bindings.*.ticket_type_id.required_with' => 'В привязке не указан тип билета',
            'data.bindings.*.ticket_type_id.uuid' => 'Тип билета должен быть UUID',
            'data.bindings.*.ticket_type_id.exists' => 'Указанный тип билета не существует',
        ];
    }
}
