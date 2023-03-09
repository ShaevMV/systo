<?php

namespace App\Console\Commands;

use App\Models\Ordering\OrderTicketModel;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Order\OrderTicket\Inspectors\CheckStatusChangeInspector;
use Tickets\Order\Shared\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Criteria\FilterOperator;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Status;

class CheckCreateTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверить на наличие билета';

    public function handle(
        CheckStatusChangeInspector     $changeInspector,
        OrderTicketRepositoryInterface $orderTicketRepository,
    ): int
    {
        $responseList = $orderTicketRepository->getList(Filters::fromValues([
            [
                'field' => OrderTicketModel::TABLE . '.status',
                'operator' => FilterOperator::EQUAL,
                'value' => Status::PAID,
            ]
        ]));

        $exceptions = [];
        foreach ($responseList as $response) {
            $exception = $changeInspector->checkIsCreate($response);
            if(!empty($exception)) {
                $exceptions[]=$exception;
            }
        }

        if (count($exceptions) > 0) {
            throw new \DomainException(implode(';', $exceptions));
        }

        return CommandAlias::SUCCESS;
    }
}
