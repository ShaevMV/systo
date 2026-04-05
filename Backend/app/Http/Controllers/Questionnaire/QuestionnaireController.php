<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessReplayNotificationQuestionnaire;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Questionnaire\Service\QuestionnaireValidationService;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
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
        QuestionnaireValidationService   $validationService,
        OrderTicketRepositoryInterface   $orderTicketRepository,
        string                           $orderId,
        string                           $ticketId,
    ): JsonResponse
    {
        $data = $request->toArray();
        $questionnaireData = $data['questionnaire'] ?? [];

        // Получаем тип анкеты по заказу
        $questionnaireTypeId = null;
        try {
            $orderTicket = $orderTicketRepository->findOrder(
                new \Shared\Domain\ValueObject\Uuid($orderId)
            );
            if ($orderTicket) {
                $questionnaireTypeId = $orderTicket->getQuestionnaireTypeId()?->value();
            }
        } catch (\Throwable $e) {
            // Игнорируем ошибку
        }

        // Валидация через сервис
        $errors = $validationService->validate($questionnaireTypeId, $questionnaireData);

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => 'Ошибка валидации'
            ], 422);
        }

        try {
            if (isset($data['questionnaire'])) {
                $data['questionnaire']['ticket_id'] = $ticketId;
                $data['questionnaire']['order_id'] = $orderId;
                $data['questionnaire']['status'] = 'APPROVE';
                $data['questionnaire']['questionnaire_type_id'] = $questionnaireTypeId;
                $questionnaireApplication->create(
                    QuestionnaireTicketDto::fromState(
                        $data['questionnaire']
                    )
                );
                if ($questionnaireDto = $questionnaireRepository->findByEmail($data['questionnaire']['email'] ?? null)) {
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
        QuestionnaireValidationService   $validationService,
    ): JsonResponse
    {
        $data = $request->toArray();
        $questionnaireData = $data['questionnaire'] ?? [];

        // Получаем тип анкеты "Анкета нового пользователя" по коду
        $questionnaireType = \App\Models\Questionnaire\QuestionnaireTypeModel::where('code', 'new_user')
            ->where('active', true)
            ->first();

        $questionnaireTypeId = $questionnaireType?->id;

        // Валидация через сервис
        $errors = $validationService->validate($questionnaireTypeId, $questionnaireData);

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => 'Ошибка валидации'
            ], 422);
        }

        try {
            if (isset($data['questionnaire'])) {
                $data['questionnaire']['status'] = 'NEW';
                $data['questionnaire']['questionnaire_type_id'] = $questionnaireTypeId;
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
            'questionnaire' => $questionnaireApplication->getItemId($id)->toArray(),
        ]);
    }

    /**
     * Получить тип анкеты по order_id и ticket_id (с вопросами)
     */
    public function getQuestionnaireTypeByOrderTicket(
        string $orderId,
        string $ticketId,
        OrderTicketRepositoryInterface $orderTicketRepository,
    ): JsonResponse
    {
        try {
            $orderTicket = $orderTicketRepository->findOrder(new \Shared\Domain\ValueObject\Uuid($orderId));
            
            if (!$orderTicket || !$orderTicket->getQuestionnaireTypeId()) {
                // Возвращаем тип анкеты "Гостевая анкета" по коду
                $questionnaireType = \App\Models\Questionnaire\QuestionnaireTypeModel::where('code', 'guest')
                    ->where('active', true)
                    ->first();

                if (!$questionnaireType) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Тип анкеты не найден'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'questionnaire_type' => $questionnaireType->toArray(),
                ]);
            }

            $questionnaireType = \App\Models\Questionnaire\QuestionnaireTypeModel::find(
                $orderTicket->getQuestionnaireTypeId()->value()
            );

            if (!$questionnaireType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип анкеты не найден'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'questionnaire_type' => $questionnaireType->toArray(),
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }
    }
}
