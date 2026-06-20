<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Baza\EntryOutbox\Applications\EntryOutboxApplication;
use Illuminate\Console\Command;

/**
 * Дренаж буфера вебхука «билет прошёл» Baza→org (Ф4).
 *
 * Запускается планировщиком (Kernel::schedule, everyMinute) — на офлайн-ноутбуке КПП доезжает,
 * когда появляется сеть. Канал выключен (нет ORG_WEBHOOK_URL/TOKEN) → ничего не шлёт, буфер копится.
 */
class DrainEntryOutboxCommand extends Command
{
    protected $signature = 'baza:drain-entry-outbox';

    protected $description = 'Слить буфер вебхука «билет прошёл» на org';

    public function handle(EntryOutboxApplication $application): int
    {
        $sent = $application->drain();
        $this->info("Отправлено на org событий входа: {$sent}");

        return Command::SUCCESS;
    }
}
