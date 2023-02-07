<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderTicketsRequest;
use App\Http\Requests\FilterForTicketOrder;
use App\Models\User;
use Database\Seeders\FestivalSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Application\TotalNumber\TotalNumber;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

class OrderTickets extends Controller
{
    public function __construct(
        private CreateOrder $createOrder,
        private AccountApplication $accountApplication,
        private PriceService $priceService,
        private GetOrder $getOrder,
        private TotalNumber $totalNumber,
        private ChanceStatus $chanceStatus,
        private TicketApplication $ticketApplication,
        private AddComment $addComment,
    ) {
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

            // Получение цены
            $priceDto = $this->priceService->getPriceDto(
                new Uuid($createOrderTicketsRequest->ticket_type_id),
                count($createOrderTicketsRequest->guests),
                $createOrderTicketsRequest->promo_code
            );
            $data = $createOrderTicketsRequest->toArray();
            $data['festival_id'] = FestivalSeeder::ID_FOR_2023_FESTIVAL;
            $orderTicketDto = OrderTicketDto::fromState(
                $data,
                $userId,
                $priceDto,
            );

            $this->createOrder->createAndSave($orderTicketDto);
            // Добавления комментария
            if($createOrderTicketsRequest->comment) {
                $this->addComment->send(
                    $orderTicketDto->getId(),
                    $userId,
                    $createOrderTicketsRequest->comment
                );
            }

            return response()->json([
                'success' => true,
                'massage' => 'Мы удачно зарегистрировали ваш заказ скоро мы его проверим и вы получите свои билеты! <br/>
              Так же мы создали нового пользователя и отправили вам на почту данные для авторизации',
            ]);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage(),
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
            OrderFilterQuery::fromState($filterForTicketOrder->toArray())
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
        $orderItem = $this->getOrder->getItemById(new Uuid($id));
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

        return response()->json($orderItem->toArray());
    }

    /**
     * Сменить статус заказа
     *
     * @throws Throwable
     */
    public function toChanceStatus(string $id, Request $request): JsonResponse
    {
        try {
            $status = new Status($request->get('status'));
            $this->chanceStatus->chance(
                new Uuid($id),
                $status
            );

            return response()->json([
                'success' => true,
                'status' => [
                    'name' => $request->get('status'),
                    'humanStatus' => $status->getHumanStatus(),
                    'listCorrectNextStatus' => $status->getListNextStatus(),
                ]
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'massage' => $throwable->getMessage()
            ], 422);
        }
    }

    /**
     * Получить список билетов в PDF
     *
     * @param  string  $id
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
                'massage' => $throwable->getMessage()
            ], 422);
        }
    }
}
