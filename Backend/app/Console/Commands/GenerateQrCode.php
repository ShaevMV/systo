<?php

namespace App\Console\Commands;

use Shared\Domain\ValueObject\Uuid;
use Carbon\Carbon;
use Database\Seeders\TypeTicketsSeeder;
use DB;
use Illuminate\Console\Command;
use Shared\Services\CreatingQrCodeService;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tickets\Ticket\Live\Service\TicketLiveService;

class GenerateQrCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:qr {start} {finish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерация QR кодов';

    public function handle(): int
    {
        $service = new CreatingQrCodeService();

        for ($i = $this->argument('start'); $i <= $this->argument('finish'); $i++) {
            $number = TicketLiveService::encrypt($i);
            echo $i . '
';
            $qrCode = $service->createQrCode(
                'https://org.spaceofjoy.ru/ticket/live/.'.$number, '');
            $qrCode->saveToFile(__DIR__ . '/QR/' . TicketLiveService::addZero($i) . ".png");
        }

        return CommandAlias::SUCCESS;
    }

}
