<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Service;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

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
            $rules = $this->buildValidationRules($questionnaireTypeId, $answers, $messages);
        } else {
            // Фоллбэк на стандартную валидацию для гостевой анкеты
            $rules = $this->getDefaultValidationRules();
            $messages = $this->getDefaultValidationMessages();
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
     * @param array $answers
     * @param array $messages
     * @return array
     */
    private function buildValidationRules(string $questionnaireTypeId, array $answers, array &$messages): array
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

            // Type validation
            $type = $question['type'] ?? 'string';
            switch ($type) {
                case 'number':
                    $fieldRules[] = 'integer';
                    $messages[$fieldName . '.integer'] = $question['title'] . ' должно быть числом';
                    break;
                case 'text':
                case 'string':
                default:
                    $fieldRules[] = 'string';
                    break;
            }

            // Regex validation
            if (!empty($question['validate'])) {
                $fieldRules[] = 'regex:' . $question['validate'];
                if (!empty($question['validate_message'])) {
                    $messages[$fieldName . '.regex'] = $question['validate_message'];
                }
            }

            // Unique validation for telegram
            if ($fieldName === 'telegram' && !empty($answers['telegram'])) {
                $fieldRules[] = 'unique:questionnaire,telegram';
                $messages[$fieldName . '.unique'] = 'Этот telegram уже занят.';
            }

            // Email validation
            if ($fieldName === 'email' && !empty($answers['email'])) {
                $fieldRules[] = 'email';
                $messages[$fieldName . '.email'] = 'Некорректный формат email';
            }

            $rules[$fieldName] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Стандартные правила валидации для гостевой анкеты
     *
     * @return array
     */
    private function getDefaultValidationRules(): array
    {
        return [
            'telegram' => [
                'nullable',
                'string',
                'min:5',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:questionnaire,telegram',
            ],
            'agy' => [
                'nullable',
                'integer',
            ],
        ];
    }

    /**
     * Стандартные сообщения об ошибках для гостевой анкеты
     *
     * @return array
     */
    private function getDefaultValidationMessages(): array
    {
        return [
            'telegram.min' => 'должен содержать минимум 5 символов.',
            'telegram.max' => 'не может превышать 32 символа.',
            'telegram.regex' => 'Разрешены только латинские буквы (a-z), цифры (0-9) и подчеркивание (_).',
            'telegram.unique' => 'Этот telegram уже занят.',
            'agy.integer' => 'Возраст только цифрами',
        ];
    }
}
