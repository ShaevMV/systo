<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Shared\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Shared\Questionnaire\Domain\DomainEvent\ProcessReplayNotificationQuestionnaire;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Throwable;

class QuestionnaireController extends Controller
{

    public function loadQuestionnaireList(
        Request                  $request,
        QuestionnaireApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'questionnaireList' => $application->getList(new QuestionnaireGetListQuery(
                $request->get('email'),
                $request->get('telegram'),
                $request->get('vk'),
                $request->get('is_have_in_club'),
                $request->get('status'),
            ))->toArray()
        ]);
    }

    /**
     * Записать анкету
     *
     * @throws Throwable
     */
    public function setQuestionnaire(
        Request                          $request,
        QuestionnaireApplication         $questionnaireApplication,
        QuestionnaireRepositoryInterface $questionnaireRepository,
        string                           $orderId,
        string                           $ticketId,
    ): JsonResponse
    {
        $request->validate([
            'questionnaire.telegram' => [
                'string',
                'min:5',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:questionnaire,telegram',
            ],
            'questionnaire.agy' => [
                'integer',
            ],
        ],[
            'questionnaire.telegram.min' => 'должен содержать минимум 5 символов.',
            'questionnaire.telegram.max' => 'не может превышать 32 символа.',
            'questionnaire.telegram.regex' => 'Разрешены только латинские буквы (a-z), цифры (0-9) и подчеркивание (_).',
            'questionnaire.telegram.unique' => 'Этот telegram уже занят.',
            'questionnaire.agy' => 'Возраст только цифрами',
        ]);

        $data = $request->toArray();
        try {
            if (isset($data['questionnaire'])) {
                $data['questionnaire']['ticket_id'] = $ticketId;
                $data['questionnaire']['order_id'] = $orderId;
                $data['questionnaire']['status'] = 'APPROVE';
                $questionnaireApplication->create(
                    QuestionnaireTicketDto::fromState(
                        $data['questionnaire']
                    )
                );
                if ($questionnaireDto = $questionnaireRepository->findByEmail($data['questionnaire']['email'])) {
                    $questionnaireApplication->sendTelegram($questionnaireDto->getId());
                };
            }
            return response()->json([
                'success' => true,
                'message' => 'Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо'
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }
    }


    /**
     * Записать анкету нового пользователя
     *
     * @throws Throwable
     */
    public function setNewUserQuestionnaire(
        Request                  $request,
        QuestionnaireApplication $questionnaireApplication,
    ): JsonResponse
    {
        $request->validate([
            'questionnaire.telegram' => [
                'string',
                'min:5',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:questionnaire,telegram',
            ],
            'questionnaire.agy' => [
                'integer',
            ],
        ],[
            'questionnaire.telegram.min' => 'должен содержать минимум 5 символов.',
            'questionnaire.telegram.max' => 'не может превышать 32 символа.',
            'questionnaire.telegram.regex' => 'Разрешены только латинские буквы (a-z), цифры (0-9) и подчеркивание (_).',
            'questionnaire.telegram.unique' => 'Этот telegram уже занят.',
            'questionnaire.agy' => 'Возраст только цифрами',
        ]);

        $data = $request->toArray();
        try {
            if (isset($data['questionnaire'])) {
                $data['questionnaire']['status'] = 'NEW';
                $questionnaireApplication->create(
                    QuestionnaireTicketDto::fromState(
                        $data['questionnaire']
                    )
                );
            }
            return response()->json([
                'success' => true,
                'message' => 'Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо'
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }
    }

    /**
     * Повторно отправить письмо на заполнение анкеты
     *
     * @param Request $request
     * @param \Bus $bus
     * @param string $id
     * @return JsonResponse
     */
    public function replayNotificationUser(
        Request $request,
        \Bus    $bus,
        string  $id,
    ): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $bus::chain([new ProcessReplayNotificationQuestionnaire(
            $request->get('email'),
            $id,
        )]);

        return response()->json([
            'success' => true,
            'message' => 'Ссылка на анкету отправлена'
        ]);
    }

    /**
     * @throws Throwable
     */
    public function approve(
        QuestionnaireApplication $questionnaireApplication,
        int                      $id,
    ): JsonResponse
    {
        $questionnaireApplication->approve($id);

        return response()->json([
            'success' => true,
            'message' => 'Анкета одобрена'
        ]);
    }

    public function getQuestionnaire(
        QuestionnaireApplication $questionnaireApplication,
        int                      $id,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'questionnaire' => $questionnaireApplication->getItemId($id)->first()->toArray(),
        ]);
    }
}
