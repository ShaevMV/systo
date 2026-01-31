<?php

namespace App\Console\Commands;

use App\Models\LiveTicketModel;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\ParkingTicketRepositoryInterface;
use Illuminate\Console\Command;
use Endroid\QrCode\Builder\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class CreateParkingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:crateParking {start} {end} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать парковочный билет {начало} {конец} {тип парковки}';

    /**
     * Execute the console command.
     *
     * @param LiveTicketRepositoryInterface $repository
     * @return int
     */
    public function handle(ParkingTicketRepositoryInterface $repository): int
    {
        LiveTicketModel::truncate();
        if ($this->argument('start') > $this->argument('end')) {
            $this->error('Начало не может быть больше конца');
            return Command::FAILURE;
        }
        $repository->create(
            (int)$this->argument('start'),
            (int)$this->argument('end'),
            $this->argument('type')
        );

        for ($i = (int)$this->argument('start'); $i <= (int)$this->argument('end'); $i++) {
            $number = $this->addZero($i);
            $qrCode = $this->createQrCode($number, $this->argument('type'));
            $qrCode->saveToFile(__DIR__ . '/QR/' . $this->argument('type') . '_' . $number . ".png");
        }

        $this->error('Созданы парковочные билеты от ' . $this->argument('start') . ' до ' . $this->argument('end') . ' типа ' . $this->argument('type'));

        return Command::SUCCESS;
    }


    private function addZero(int $number): string
    {
        $zero = '';

        if ($number < 1000) {
            $zero .= '0';
        }
        if ($number < 100) {
            $zero .= '0';
        }
        if ($number < 10) {
            $zero .= '0';
        }

        return $zero . $number;
    }

    private function createQrCode(string $ticketId, string $prefix): ResultInterface
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($prefix . $ticketId)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->labelText('') //TODO: Автомотизировать
            ->labelFont(new OpenSans(16))
            ->labelAlignment(new LabelAlignmentCenter())
            ->validateResult(false)
            ->build();
    }

    public function createPdf(string $id, string $name, string $email, string $prefix, ?string $project = null): \Barryvdh\DomPDF\PDF
    {
        $qrCode = $this->createQrCode($id, $prefix);

        return Pdf::loadView('pdf2', [
            'url' => $qrCode->getDataUri(),
            'name' => $name,
            'email' => $email,
            'kilter' => $id,
            'project' => $project
        ]);
    }
}
