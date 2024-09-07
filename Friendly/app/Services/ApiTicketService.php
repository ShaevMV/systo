<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessSendListTicketEmail;
use App\Models\Auto;
use App\Models\ListTicket;
use App\Services\DTO\CreateApiTicketDTO;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Shared\Services\TicketService;
use Throwable;

class ApiTicketService
{
    public function __construct(
        private TicketService $ticketService,
    ) {

    }

    /**
     * @throws Throwable
     */
    public function create(
        CreateApiTicketDTO $apiTicketDTO
    ): bool
    {
        DB::beginTransaction();
        try {
            foreach ($apiTicketDTO->getAuto() as $item) {
                if(empty(trim($item))) {
                    continue;
                }
                $model = new Auto();
                $model->auto = $item;
                $model->project = $apiTicketDTO->getProject();
                $model->curator = $apiTicketDTO->getCurator();
                $model->comment = $apiTicketDTO->getComment();
                $model->festival_id = $apiTicketDTO->getFestivalId();
                $model->user_id = $apiTicketDTO->getUserId();
                $model->saveOrFail();
                $this->ticketService->pushAutoList($model);
            }
            $ids = [];
            foreach ($apiTicketDTO->getList() as $item) {
                if(empty(trim($item))) {
                    continue;
                }
                $model = new ListTicket();
                $model->fio = $item;
                $model->project = $apiTicketDTO->getProject();
                $model->curator = $apiTicketDTO->getCurator();
                $model->email = $apiTicketDTO->getEmail();
                $model->comment = $apiTicketDTO->getComment();
                $model->festival_id = $apiTicketDTO->getFestivalId();
                $model->user_id = $apiTicketDTO->getUserId();
                $model->phone = $apiTicketDTO->getPhone();
                $model->saveOrFail();

                $ids['S' . $model->id] = $item;
                $this->ticketService->pushTicketList($model, $apiTicketDTO->getFestivalId());
            }

            if (count($ids) > 0) {
                Bus::chain([
                    new ProcessSendListTicketEmail(
                        $apiTicketDTO->getEmail(),
                        $ids,
                        $apiTicketDTO->getProject()
                    ),
                ])->dispatch();
            }
            DB::commit();
            return true;
        } catch (Throwable $exception) {
            DB::rollback();
            throw $exception;
        }


    }
}
