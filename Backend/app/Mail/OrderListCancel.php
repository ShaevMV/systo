<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Festival\FestivalModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;

class OrderListCancel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Uuid $festivalId,
    ) {
    }

    public function build(): static
    {
        $festivalName = FestivalModel::query()
            ->whereId($this->festivalId->value())
            ->value('name') ?? '';

        $this->subject('Список на ' . $festivalName . ' отменён');

        return $this->view('email.orderListCancel', [
            'festivalName' => $festivalName,
        ]);
    }
}
