<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Bus\EventJobs\DomainEvent;

class ProcessTelegramSend implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $username)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws GuzzleException
     */
    public function handle()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            'http://77.222.60.58:8000',
            [
                'username' => $this->username,
                'token' => 'sy-HShs-0d7a-psdM-19Bw',
            ]
        );
        Log::info($this->username);
        if ($response->getStatusCode() !== 200) {
            throw new \DomainException('не отправлен ' . $this->username . ' ответ ' . $response->getBody()->getContents() , $response->getStatusCode());
        }

        Log::info($response->getBody()->getContents());

    }
}
