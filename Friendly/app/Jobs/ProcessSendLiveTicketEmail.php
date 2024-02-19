<?php

namespace App\Jobs;

use App\Mail\OrderLiveShipped;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessSendLiveTicketEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $email,
    )
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '256M');
        Mail::to($this->email)->send(new OrderLiveShipped($this->email));
    }
}
