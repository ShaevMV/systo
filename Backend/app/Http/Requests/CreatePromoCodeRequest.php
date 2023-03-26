<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property ?string $id
 * @property string $name
 * @property float $discount
 * @property boolean $is_percent
 * @property boolean $active
 * @property ?int $limit
 */
class CreatePromoCodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required',
                Rule::unique('promo_code', 'name')->ignore($this->id)],
            'discount' => ['required', 'numeric', function ($attribute, $value, $fail) {
                if ($value <= 0) {
                    $fail('Скидка не может быть меньше или равной 0');
                } elseif ((bool)$this->is_percent && $value > 100) {
                    $fail('Скидка при проценте не может быть больше 100');
                }
            },],
            'limit' => ['numeric', 'nullable'],
            'is_percent' => 'required|boolean',
            'active' => 'required|boolean',
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
            '*.unique' => 'Такой промокод уже есть',
            '*.numeric' => 'Поле должно быть числом',
        ];
    }
}
