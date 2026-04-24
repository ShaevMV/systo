<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessReplayNotificationQuestionnaire;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Questionnaire\Service\QuestionnaireValidationService;

class QuestionnaireController extends Controller
{
    public function loadQuestionnaireList(
        Request $request,
        QuestionnaireApplication $application,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'questionnaireList' => $application->getList(new QuestionnaireGetListQuery(
                $request->get('email'),
                $request->get('telegram'),
                $request->get('vk'),
                $request->get('is_have_in_club'),
                $request->get('status'),
                $request->get('questionnaire_type_id'),
            ))->toArray(),
        ]);
    }

    /**
     * Записать анкету
     *
     * @throws Throwable
     */
    public function setQuestionnaire(
        Request $request,
        QuestionnaireApplication $questionnaireApplication,
        QuestionnaireRepositoryInterface $questionnaireRepository,
        QuestionnaireValidationService $validationService,
        OrderTicketRepositoryInterface $orderTicketRepository,
        string $orderId,
        string $ticketId,
    ): JsonResponse {
        $data = $request->toArray();
        $questionnaireData = $data['questionnaire'] ?? [];

        // Получаем тип анкеты по заказу
        $questionnaireTypeId = null;
        try {
            $uuid = new \Shared\Domain\ValueObject\Uuid($orderId);
            $orderTicket = $orderTicketRepository->findOrder($uuid);
            if ($orderTicket) {
                $questionnaireTypeId = $orderTicket->getQuestionnaireTypeId()?->value();
            }
        } catch (\Throwable) {
            // Игнорируем ошибку
        }

        // Fallback: если заказ не найден или нет questionnaire_type_id — используем гостевую анкету
        if ($questionnaireTypeId === null) {
            $guestQuestionnaireType = \App\Models\Questionnaire\QuestionnaireTypeModel::where('code', 'guest')
                ->where('active', true)
                ->first();
            if ($guestQuestionnaireType) {
                $questionnaireTypeId = $guestQuestionnaireType->id;
            }
        }

        // Валидация через сервис
        $errors = $validationService->validate($questionnaireTypeId, $questionnaireData);

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => 'Ошибка валидации',
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
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо',
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }

    /**
     * Записать анкету нового пользователя
     *
     * @throws Throwable
     */
    public function setNewUserQuestionnaire(
        Request $request,
        QuestionnaireApplication $questionnaireApplication,
        QuestionnaireValidationService $validationService,
    ): JsonResponse {
        $data = $request->toArray();
        $questionnaireData = $data['questionnaire'] ?? [];

        // Получаем тип анкеты "Анкета нового пользователя" по коду
        $questionnaireType = \App\Models\Questionnaire\QuestionnaireTypeModel::where('code', 'new_user')
            ->where('active', true)
            ->first();

        $questionnaireTypeId = $questionnaireType?->id;

        // Валидация через сервис
        $errors = $validationService->validate($questionnaireTypeId, $questionnaireData);

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => 'Ошибка валидации',
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
                'message' => 'Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо',
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }

    /**
     * Повторно отправить письмо на заполнение анкеты
     *
     * @param  Request  $request
     * @param  \Bus  $bus
     * @param  string  $id
     * @return JsonResponse
     */
    public function replayNotificationUser(
        Request $request,
        \Bus $bus,
        string $id,
    ): JsonResponse {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $bus::chain([new ProcessReplayNotificationQuestionnaire(
            $request->get('email'),
            $id,
        )]);

        return response()->json([
            'success' => true,
            'message' => 'Ссылка на анкету отправлена',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function approve(
        QuestionnaireApplication $questionnaireApplication,
        int $id,
    ): JsonResponse {
        $questionnaireApplication->approve($id);

        return response()->json([
            'success' => true,
            'message' => 'Анкета одобрена',
        ]);
    }

    public function getQuestionnaire(
        QuestionnaireApplication $questionnaireApplication,
        int $id,
    ): JsonResponse {
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
        QuestionnaireApplication $questionnaireApplication,
    ): JsonResponse {
        try {
            $orderUuid = new \Shared\Domain\ValueObject\Uuid($orderId);
            $ticketUuid = new \Shared\Domain\ValueObject\Uuid($ticketId);

            $questionnaireType = $questionnaireApplication->getQuestionnaireTypeByOrderTicket($orderUuid, $ticketUuid);

            if (! $questionnaireType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип анкеты не найден',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'questionnaire_type' => $questionnaireType->toArray(),
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }

    /**
     * Получить анкету по order_id и ticket_id для предзаполнения
     */
    public function getByOrderTicket(
        string $orderId,
        string $ticketId,
        QuestionnaireApplication $questionnaireApplication,
    ): JsonResponse {
        try {
            $orderUuid = new \Shared\Domain\ValueObject\Uuid($orderId);
            $ticketUuid = new \Shared\Domain\ValueObject\Uuid($ticketId);

            $questionnaire = $questionnaireApplication->getByOrderTicket($orderUuid, $ticketUuid);

            return response()->json([
                'success' => true,
                'questionnaire' => $questionnaire?->toArray(),
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }

    /**
     * Загрузить фото участника для бейджа.
     * Фото сохраняется в storage/app/public/badges/{festival_id}/{ticketId}.ext
     */
    public function uploadPhoto(
        Request $request,
        OrderTicketRepositoryInterface $orderTicketRepository,
        string $orderId,
        string $ticketId,
    ): JsonResponse {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $order = $orderTicketRepository->findOrder(new Uuid($orderId));

            if ($order === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заказ не найден',
                ], 404);
            }

            $festivalId = $order->getFestivalId()->value();
            $extension  = $request->file('photo')->getClientOriginalExtension();
            $filename   = $ticketId . '.' . $extension;
            $path       = 'badges/' . $festivalId . '/' . $filename;

            Storage::disk('public')->putFileAs(
                'badges/' . $festivalId,
                $request->file('photo'),
                $filename,
            );

            $host     = \App::isLocal() ? 'http://org.tickets.loc' : 'https://org.spaceofjoy.ru';
            $photoUrl = $host . '/storage/' . $path;

            return response()->json([
                'success'   => true,
                'photo_url' => $photoUrl,
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }
}
