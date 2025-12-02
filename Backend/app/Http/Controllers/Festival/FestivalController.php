<?php

declare(strict_types=1);

namespace App\Http\Controllers\Festival;

use App\Http\Controllers\Controller;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Application\GetFestivalList\FestivalApplication;

class FestivalController extends Controller
{
    public function __construct(
        private FestivalApplication $festivalApplication
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function getFestivalList(): array
    {
        return $this->festivalApplication->getAllFestival()->toArray();
    }
}
