<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessReplayNotificationQuestionnaire;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireController extends Controller
{

    public function loadQuestionnaireList(
        Request                  $request,
        QuestionnaireApplication $application,
    ): JsonResponse
    {
        try {
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
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile(),
                'trace' => $throwable->getTrace(),
            ]);
        }
    }

    /**
     * Записать анкету
     *
     * @throws Throwable
     */
    public function setQuestionnaire(
        Request                  $request,
        QuestionnaireApplication $questionnaireApplication,
        string                   $orderId,
        string                   $ticketId,
    ): JsonResponse
    {
        $data = $request->toArray();
        try {
            if (isset($data['questionnaire'])) {
                $data['questionnaire']['ticket_id'] = $ticketId;
                $data['questionnaire']['order_id'] = $orderId;
                $data['questionnaire']['status'] = QuestionnaireStatus::APPROVE;
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
     * Записать анкету нового пользователя
     *
     * @throws Throwable
     */
    public function setNewUserQuestionnaire(
        Request                  $request,
        QuestionnaireApplication $questionnaireApplication,
    ): JsonResponse
    {
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
        int                   $id,
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
