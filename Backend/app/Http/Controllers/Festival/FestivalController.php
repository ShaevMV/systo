<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Application\GetInfoForOrder\GetInfoForOrder;
use Tickets\Order\OrderTicket\Application\GetFestivalList\FestivalApplication;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class FestivalController extends Controller
{
    public function __construct(
        private FestivalApplication $festivalApplication,
        private GetInfoForOrder    $allInfoForOrderingTicketsSearcher,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function getFestivalList(): array
    {
        return $this->festivalApplication->getAllFestival()->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getInfoForOrder(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }
        $isAdmin = filter_var($request->get('is_admin', false),FILTER_VALIDATE_BOOLEAN);
        $role = Auth::user()?->role;
        $isPusher = in_array($role, [AccountRoleHelper::pusher, AccountRoleHelper::pusher_curator], true);

        return $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(
                new Uuid($request->get('festival_id')),
                $isAdmin,
                $isPusher
            )
            ->toArray();
    }

    /**
     * @throws JsonException
     */
    public function loadByTicketType(
        string $ticketTypeId,
    ): array
    {
        return $this->allInfoForOrderingTicketsSearcher
            ->getListTypesOfPaymentDto(new Uuid($ticketTypeId))
            ->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getPriceList(Request $request): array
    {
        if (is_null($request->get('festival_id'))) {
            throw new DomainException('Не задан идентификатор фестиваля');
        }

        return $this->allInfoForOrderingTicketsSearcher->getAllPrice(new Uuid($request->get('festival_id')))->toArray();
    }


    /**
     * @throws JsonException
     */
    public function getTicketTypeList(): array
    {
        return $this->allInfoForOrderingTicketsSearcher->getListTicketTypeDto(new Uuid('9d679bcf-b438-4ddb-ac04-023fa9bff4b8'))->toArray();
    }

}
