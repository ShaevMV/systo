<?php

namespace App\Console\Commands;

use App\Models\LiveTicketModel;
use Baza\Shared\Domain\ValueObject\Status;
use Illuminate\Console\Command;

class BanListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:banList';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Бан лист на живые билеты';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ids = '525,526,527,528,529,573,574,575,576,577,578,583,584,585,586,587,588,589,590,591,592,607,608,608,609,610,611,612,613,616,617,618,619,620,621,622,623,624,625,626,627,628,629,630,631,632,638,639,640';
        $idsArr = explode(',',$ids);

        foreach ($idsArr as $item) {
            LiveTicketModel::find($item)->update([
                'status' => Status::CANCEL,
                'comment' => 'ВНИМАНИЕ! С этим билетом необходимо задержать человека и вызвать охранника Артема (Темыча)',
            ]);
        }

        return Command::SUCCESS;
    }
}
