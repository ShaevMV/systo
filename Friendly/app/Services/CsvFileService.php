<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\DTO\CreateApiTicketDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CsvFileService
{
    public function __construct(
        private ApiTicketService $ticketService
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function insertListInFile(UploadedFile $file, string $festivalId, int $userId): bool
    {
        $rows = explode("\r\n", $file->getContent());
        if (0 === count($rows)) {
            return false;
        }

        unset($rows[0]);
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $data = explode(",", $row);
                $this->ticketService->create(CreateApiTicketDTO::fromState([
                    'project' => $data[1],
                    'curator' => $data[0],
                    'festival_id' => $festivalId,
                    'email' => $data[2],
                    'list' => [$data[3]],
                    'auto' => [$data[5]],
                    'phone' => '',
                    'comment' => $data[4],
                ], $userId));
            }
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollback();
            throw $exception;
        }

        return true;
    }
}
