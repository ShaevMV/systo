<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessReplayNotificationQuestionnaire;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireController extends Controller
{

    public function loadQuestionnaireList(
        Request $request,
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
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Записать анкету
     *
     * @throws \Throwable
     */
    public function setQuestionnaire(
        Request $request,
        QuestionnaireApplication $questionnaireApplication,
        string $orderId,
        string $ticketId,
    ): JsonResponse
    {
        $data = $request->toArray();
        try {
            if(isset($data['questionnaire'])) {
                $questionnaireApplication->create(
                    QuestionnaireTicketDto::fromState(
                        $data['questionnaire'],
                        new Uuid($orderId),
                        new Uuid($ticketId),
                    )
                );
            }
            return response()->json([
                'success' => true,
                'message' => 'Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо'
            ]);
        } catch (\Throwable $throwable) {
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
        \Bus $bus,
        string $id,
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

    public function approve(
        QuestionnaireApplication $questionnaireApplication,
        string $id,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Ссылка на анкету отправлена'
        ]);
    }

    public function getQuestionnaire(
        QuestionnaireApplication $questionnaireApplication,
        string $id,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Ссылка на анкету отправлена'
        ]);
    }
}
