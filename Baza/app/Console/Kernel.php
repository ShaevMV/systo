<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Дренаж буфера вебхука «билет прошёл» Baza→org (Ф4). Канал выключен (нет
        // ORG_WEBHOOK_URL/TOKEN) → команда ничего не шлёт. Требует запущенного `schedule:run`
        // (cron/supervisord) на ноутбуке КПП — см. infra-следствие в плане Ф4.
        $schedule->command('baza:drain-entry-outbox')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
