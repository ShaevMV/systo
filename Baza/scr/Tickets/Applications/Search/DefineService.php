<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use InvalidArgumentException;
use Baza\Shared\Domain\ValueObject\Uuid;

class DefineService
{
    public const ELECTRON_TICKET = 'electron';
    public const SPISOK_TICKET = 'spisok';
    public const LIVE_TICKET = 'live';
    public const FRIENDLY_TICKET = 'friendly';

    public const HUMAN_LIST = [
        self::ELECTRON_TICKET => 'Электронный',
        self::SPISOK_TICKET => 'Список',
        self::LIVE_TICKET => 'Живой',
        self::FRIENDLY_TICKET => 'Френдли',
    ];

    public const PREFIX_LIST = [
        self::ELECTRON_TICKET => 'E-',
        self::SPISOK_TICKET => 'S',
        self::LIVE_TICKET => '',
        self::FRIENDLY_TICKET => 'f',
    ];

    private const URL = [
        'http://baza.spaceofjoy.ru',
        '/search?q=',
    ];

    private const ELECTRON_TICKET_URL = '/newTickets/';
    private const LIVE_TICKET_URL = '/live?id=';

    public function getTypeByReference(string $origLink): SearchDto
    {
        $link = str_replace(self::URL, '', $origLink);

        if (strripos($link, self::ELECTRON_TICKET_URL) !== false) {
            $type = self::ELECTRON_TICKET;
            $uuid = str_replace(self::ELECTRON_TICKET_URL, '', $link);
            $id = new Uuid($uuid);
        } elseif (strripos($link, self::LIVE_TICKET_URL) !== false) {
            $type = self::LIVE_TICKET;
            $id = (int)str_replace(self::LIVE_TICKET_URL, '', $link);
        } elseif (strripos($link, 's') !== false) {
            $type = self::SPISOK_TICKET;
            $id = $this->getOnlyNumber($link);
        } elseif (strripos($link, 'f') !== false) {
            $type = self::FRIENDLY_TICKET;
            $id = $this->getOnlyNumber($link);
        } else {
            throw new InvalidArgumentException('Данная ссылка не опознана ' . $origLink);
        }

        return new SearchDto($type, $id);

    }

    private function getOnlyNumber(string $str): int
    {
        return (int)preg_replace("/[^,.0-9]/", '', $str);
    }
}
