<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\DomainEvent;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\Questionnaire\Services\TelegramSendService;

class ProcessTelegramByQuestionnaireSend implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $email)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws GuzzleException
     */
    public function handle(
        QuestionnaireRepositoryInterface $questionnaireRepository
    )
    {
        if(($dto = $questionnaireRepository->findByEmail($this->email)) && $dto->getTelegram()) {
            TelegramSendService::send($dto->getTelegram());
        }
    }
}
