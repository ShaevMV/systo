<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Festival\FestivalModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;

class OrderListDifficultiesArose extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private string $comment,
        private Uuid   $festivalId,
    ) {
    }

    public function build(): static
    {
        $festivalName = FestivalModel::query()
            ->whereId($this->festivalId->value())
            ->value('name') ?? '';

        $this->subject('Возникли трудности со списком на ' . $festivalName);

        return $this->view('email.orderListDifficultiesArose', [
            'festivalName' => $festivalName,
            'comment'      => $this->comment,
        ]);
    }
}
