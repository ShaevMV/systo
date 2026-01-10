<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use App\Http\Requests\FilterForTicketOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Billing\Application\Billing;
use Tickets\Billing\DTO\PaymentRequestDTO;
use Tickets\Billing\ValueObject\DeviceValueObject;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Order\OrderTicket\Application\TotalNumber\TotalNumber;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Order\OrderTicket\Service\TicketService;
use Tickets\Order\OrderTicket\Util\TicketUtil;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder        $createOrder,
        private GetTicketType      $getTicketType,
        private TicketService      $ticketService,
        private AccountApplication $accountApplication,
        private PriceService       $priceService,
        private GetOrder           $getOrder,
        private TotalNumber        $totalNumber,
        private ChanceStatus       $chanceStatus,
        private TicketApplication  $ticketApplication,
        private AddComment         $addComment,
        private Billing $billing,
        private QuestionnaireApplication $questionnaireApplication,
    )
    {
    }

    /**
     * Создать заказ
     *
     * @throws Throwable
     */
    public function create(CreateOrderTicketsRequest $createOrderTicketsRequest): JsonResponse
    {
        try {
            // Создание или получение пользователя по email
            $userId = new Uuid($this->accountApplication->creatingOrGetAccountId(
                AccountDto::fromState($createOrderTicketsRequest->toArray())
            )->value());
            $ticketTypeId = new Uuid($createOrderTicketsRequest->ticket_type_id);
            $guests = $createOrderTicketsRequest->guests;
            array_unshift($guests,[
                'value' => $createOrderTicketsRequest->masterName,
                'email' => $createOrderTicketsRequest->email,
            ]);

            // Получение цены
            $priceDto = $this->priceService->getPriceDto(
                $ticketTypeId,
                count($guests),
                $createOrderTicketsRequest->promo_code
            );

            $ticketType = $this->getTicketType->getTicketsTypeByUuid($ticketTypeId);

            $data = $createOrderTicketsRequest->toArray();
            $data['guests'] = $guests;

            $orderTicketDto = OrderTicketDto::fromState(
                $data,
                $userId,
                $priceDto,
                $ticketType->isLiveTicket(),
            );

            $this->createOrder->createAndSave($orderTicketDto);
            // Добавляем анкету
            if(isset($data['questionnaire'])) {
                $ticket = $orderTicketDto->getTicket()[0];
                $questionnaireTicketDto = QuestionnaireTicketDto::fromState(
                    array_merge($data['questionnaire'],[
                        'phone' => $createOrderTicketsRequest->phone,
                        'name' => $ticket->getValue(),
                    ]),
                    $orderTicketDto->getId(),
                    $ticket->getId(),
                );
                $this->questionnaireApplication->create($questionnaireTicketDto);
            }

            // Добавления комментария
            if ($createOrderTicketsRequest->comment) {
                $this->addComment->send(
                    $orderTicketDto->getId(),
                    $userId,
                    $createOrderTicketsRequest->comment
                );
            }
            if($orderTicketDto->isBilling()) {
                $billingResponse = $this->billing->creatingLink(
                    new PaymentRequestDTO(
                        $orderTicketDto->getId(),
                        $priceDto->getPriceItem(),
                        $priceDto->getCount(),
                        $createOrderTicketsRequest->email,
                        $createOrderTicketsRequest->phone,
                    ),
                    new DeviceValueObject(
                        request()->userAgent(),
                    ),
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Через несколько секунд откроется QR-код для оплаты. Откройте приложение вашего банка и совершите перевод. <br/>
                Если окно не открылось нажмите на кнопку ниже.<br/>
              Если Вы зарегистрировали нового пользователя, то Вы также получите на почту данные для авторизации<br/>
              <a href="' .$billingResponse->getLinkToReceipt() .'" target="_blank"> <b>Открыть ссылку для оплаты</b> </a><br/>

              ',

                    'link' => $billingResponse->getLinkToReceipt(),
                ]);

            }

            return response()->json([
                'success' => true,
                'message' => 'Мы удачно зарегистрировали ваш заказ скоро мы его проверим и вы получите свои билеты! <br/>
              Так же мы создали нового пользователя и отправили вам на почту данные для авторизации',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'link' => $exception->getLine(),
                'file' => $exception->getFile(),
            ]);
        }
    }

    /**
     * Получить список заказов от пользователя
     * @throws JsonException
     */
    public function getUserList(): JsonResponse
    {
        /** @var string $id */
        $id = Auth::id();

        return response()->json(
            [
                'list' => $this->getOrder->listByUser(new Uuid($id))?->toArray() ?? []
            ]);
    }

    /**
     * @throws JsonException
     */
    public function getList(FilterForTicketOrder $filterForTicketOrder): JsonResponse
    {
        $listResponse = $this->getOrder->listByFilter(
            OrderFilterQuery::fromState(
                $filterForTicketOrder->toArray(),
                Auth::user()->isManager() ?? false
            )
        ) ?? new ListResponse();

        return response()->json(
            [
                'list' => $listResponse->toArray(),
                'totalNumber' => $this->totalNumber->getTotalNumber($listResponse)->toArray()
            ]);
    }

    /**
     * Получить определённый заказ
     *
     * @throws JsonException
     */
    public function getOrderItem(string $id): JsonResponse
    {
        $orderUuid = new Uuid($id);
        $orderItem = $this->getOrder->getItemById($orderUuid);
        /** @var User $user */
        $user = Auth::user();
        if (is_null($orderItem) ||
            (!$orderItem->getUserId()->equals(new Uuid(Auth::id()))
                && !$user->is_admin)
        ) {
            return response()->json([
                'errors' => ['error' => 'Заказ не найден']
            ], 404);
        }

        return response()->json([
            'order' => $orderItem->toArray(),
            'questionnaire' => $this->questionnaireApplication->getItemByOrderId($orderUuid)->toArray(),
        ]);
    }

    /**
     * Сменить статус заказа
     *
     * @throws Throwable
     */
    public function toChanceStatus(string $id, Request $request): JsonResponse
    {
        if (in_array($request->get('status'), [
            Status::DIFFICULTIES_AROSE,
            Status::LIVE_TICKET_ISSUED
        ])) {
            $request->validate([
                'comment' => 'required|string'
            ], [
                '*.required' => 'Поле обязательно для ввода',
            ]);
        }

        $status = new Status($request->get('status'));
        $this->chanceStatus->chance(
            new Uuid($id),
            $status,
            new Uuid(Auth::id()),
            $request->get('comment', null)
        );

        return response()->json([
            'success' => true,
            'status' => [
                'name' => $request->get('status'),
                'humanStatus' => $status->getHumanStatus(),
                'listCorrectNextStatus' => $status->getListNextStatus(),
            ]
        ]);
    }

    /**
     * Получить список билетов в PDF
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getUrlListForPdf(string $id): JsonResponse
    {
        try {
            $urlsTicketPdfResponse = $this->ticketApplication->getPdfList(new Uuid($id));

            return response()->json([
                'success' => true,
                'listUrl' => $urlsTicketPdfResponse->getUrls()
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }
    }

    /**
     * Записать анкету
     *
     * @throws Throwable
     */
    public function setQuestionnaire(Request $request, string $orderId, string $ticketId): JsonResponse
    {
        $data = $request->toArray();
        try {
            if(isset($data['questionnaire'])) {
                $this->questionnaireApplication->create(
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
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage()
            ], 422);
        }

    }
}
