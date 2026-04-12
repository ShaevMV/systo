<?php

namespace App\Console;

use App\Models\ReportConfig;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $configs = ReportConfig::where('is_active', true)->get();

            $now = now();

            foreach ($configs as $config) {
                $cron = new CronExpression($config->cron_expression);
                $tz = new \DateTimeZone($config->timezone);

                if ($cron->isDue($now->copy()->setTimezone($tz)->format('Y-m-d H:i'), $tz)) {
                    $this->artisan('reports:export-to-google', ['--config-id' => $config->id]);
                }
            }
        })->everyMinute()->withoutOverlapping();
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
