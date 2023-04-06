<?php

namespace App\Jobs;

use App\Mail\OrderShippedList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessSendListTicketEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $email;
    private string $project;
    private array $ids;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $email,
        array $ids,
        string $project
    )
    {
        $this->email = $email;
        $this->ids = $ids;
        $this->project = $project;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new OrderShippedList(
            $this->ids,
            $this->email,
            $this->project
        ));
    }
}
