<?php

namespace App\Console\Commands;

use App\Models\Festival\TicketTypesModel;
use App\Models\Ordering\OrderTicketModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class PushQuestionnaireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questionnaire:push_friendly_live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправить анкеты всем живым френдли';


    /**
     * @throws \Throwable
     * @throws JsonException
     */
    public function handle(
        OrderTicketRepositoryInterface $orderTicketRepository,
        Bus                            $bus,
    ): int
    {
        $status = $this->argument('status');
        try {
            $filter = Filters::fromValues([
                [
                    'field' => TicketTypesModel::TABLE . '.is_live_ticket',
                    'operator' => FilterOperator::EQUAL,
                    'value' => true,
                ],
                [
                    'field' => OrderTicketModel::TABLE . '.festival_id',
                    'operator' => FilterOperator::EQUAL,
                    'value' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
                ],
            ]);

            foreach ($orderTicketRepository->getList($filter) as $item) {
                $this->info('Нашел заказ '.$item->getKilter());
                $guests = [];
                foreach ($item->getGuests() as $guest) {
                    $guests[] = $guest->toArray();
                    $this->info('Нашел билет '.$guest->getEmail());

                }
                $orderTicketDto = OrderTicketDto::fromState([
                    'id' => $item->getId()->value(),
                    'status' => $item->getStatus()->getName(),
                    'guests' => $guests,
                    'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
                    'email' => $item->getEmail(),
                    'phone' => $item->getPhone(),
                    'types_of_payment_id' => '613d6bb9-a3a0-480e-ade8-05625fc19544',
                    'ticket_type_id' => 'cd85c591-a991-4f4a-990c-0f7288a348f3',
                    'promo_code' => null,
                ], $item->getUserId(),
                    new PriceDto(1000, count($guests)));

                $orderTicket = OrderTicket::toProcessGuestNotificationQuestionnaire($orderTicketDto);
                $bus::chain($orderTicket->pullDomainEvents())
                    ->dispatch();
                $this->info('Отправил анкеты по заказу '.$item->getKilter());
            }

        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());
            throw $throwable;
        }

        return CommandAlias::SUCCESS;
    }
}
