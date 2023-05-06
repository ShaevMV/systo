<?php

namespace App\Console\Commands;

use App\Models\LiveTicketModel;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Illuminate\Console\Command;

class CreateLiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:crateLive {start} {end}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать живой билет {начало} {конец}';

    /**
     * Execute the console command.
     *
     * @param LiveTicketRepositoryInterface $repository
     * @return int
     */
    public function handle(LiveTicketRepositoryInterface $repository): int
    {
        LiveTicketModel::truncate();
        if ($this->argument('start') > $this->argument('end')) {
            $this->error('Начало не может быть больше конца');
            return Command::FAILURE;
        }
        $repository->create((int)$this->argument('start'), (int)$this->argument('end'));

        $this->error('Созданы живые билеты от ' . $this->argument('start') . ' до ' . $this->argument('end'));

        return Command::SUCCESS;
    }
}
