<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Service;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Illuminate\Support\Facades\Validator;

class QuestionnaireValidationService
{
    /**
     * Валидировать ответы на вопросы анкеты
     *
     * @param string|null $questionnaireTypeId ID типа анкеты
     * @param array $answers Ответы пользователя (ключ => значение)
     * @return array Массив ошибок валидации ['field' => ['error1', 'error2']]
     */
    public function validate(?string $questionnaireTypeId, array $answers): array
    {
        $rules = [];
        $messages = [];

        if ($questionnaireTypeId) {
            $rules = $this->buildValidationRules($questionnaireTypeId, $messages);
        }

        if (empty($rules)) {
            return [];
        }

        $validator = Validator::make($answers, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }

    /**
     * Построить правила валидации на основе типа анкеты
     *
     * @param string $questionnaireTypeId
     * @param array $messages
     * @return array
     */
    private function buildValidationRules(string $questionnaireTypeId, array &$messages): array
    {
        $questionnaireType = QuestionnaireTypeModel::find($questionnaireTypeId);
        
        if (!$questionnaireType || !$questionnaireType->questions) {
            return [];
        }

        $questions = is_string($questionnaireType->questions)
            ? json_decode($questionnaireType->questions, true)
            : $questionnaireType->questions;

        if (!is_array($questions)) {
            return [];
        }

        $rules = [];

        foreach ($questions as $question) {
            $fieldName = $question['name'] ?? '';
            if (empty($fieldName)) {
                continue;
            }

            $fieldRules = [];
            $isRequired = !empty($question['required']);

            // Required / Nullable
            if ($isRequired) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Laravel validation rules from JSON
            if (!empty($question['validate']) && is_string($question['validate'])) {
                $validateConfig = json_decode($question['validate'], true);

                if (is_array($validateConfig)) {
                    // Формат: {"rules": ["string", "min:5"], "messages": {"min": "Минимум 5 символов"}}
                    if (isset($validateConfig['rules']) && is_array($validateConfig['rules'])) {
                        $fieldRules = array_merge($fieldRules, $validateConfig['rules']);
                    }

                    // Custom messages
                    if (isset($validateConfig['messages']) && is_array($validateConfig['messages'])) {
                        foreach ($validateConfig['messages'] as $rule => $message) {
                            $messages[$fieldName . '.' . $rule] = $message;
                        }
                    }
                } elseif (str_starts_with($question['validate'], '/')) {
                    // Формат: строка-regex /^pattern$/
                    $pattern = $question['validate'];
                    // Проверка валидности regex
                    if (@preg_match($pattern, '') === false) {
                        \Illuminate\Support\Facades\Log::warning("Invalid regex in questionnaire field: {$fieldName} = {$pattern}");
                        continue;
                    }
                    $fieldRules[] = 'regex:' . $pattern;
                    $messages[$fieldName . '.regex'] = 'Неверный формат поля "' . ($question['title'] ?? $fieldName) . '".';
                }
            }

            // Unique validation for telegram
            if ($fieldName === 'telegram') {
                $fieldRules[] = 'unique:questionnaire,telegram';
                $messages[$fieldName . '.unique'] = 'Этот telegram уже занят.';
            }

            $rules[$fieldName] = $fieldRules;
        }

        return $rules;
    }
}
