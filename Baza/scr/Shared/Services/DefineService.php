<?php

declare(strict_types=1);

namespace Baza\Shared\Services;

use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Applications\Scan\SearchDto;
use InvalidArgumentException;

class DefineService
{
    public const AUTO_TICKET = 'auto';
    public const ELECTRON_TICKET = 'electron';
    public const SPISOK_TICKET = 'spisok';
    public const LIVE_TICKET = 'live';
    public const DRUG_TICKET = 'drug';

    public const HUMAN_LIST = [
        self::ELECTRON_TICKET => 'Электронный',
        self::AUTO_TICKET => 'Автомобилишки',
        self::SPISOK_TICKET => 'Список',
        self::LIVE_TICKET => 'Живой',
        self::DRUG_TICKET => 'Френдли',
    ];

    public const PREFIX_LIST = [
        self::ELECTRON_TICKET => 'E-',
        self::SPISOK_TICKET => 'S',
        self::LIVE_TICKET => '',
        self::AUTO_TICKET => '',
        self::DRUG_TICKET => 'f',
    ];

    private const URL = [
        'http://baza.spaceofjoy.ru',
        '/search?q=',
    ];

    public const TYPE_BY_COLONS_IN_CHANGES = [
        self::AUTO_TICKET => 'count_auto_tickets',
        self::ELECTRON_TICKET => 'count_el_tickets',
        self::SPISOK_TICKET => 'count_spisok_tickets',
        self::LIVE_TICKET => 'count_live_tickets',
        self::DRUG_TICKET => 'count_drug_tickets',
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
        } elseif (strripos($link, 's') !== false) {
            $type = self::SPISOK_TICKET;
            $id = $this->getOnlyNumber($link);
        } elseif (strripos($link, 'f') !== false) {
            $type = self::DRUG_TICKET;
            $id = $this->getOnlyNumber($link);
        } elseif ($this->getOnlyNumber($link) == $link || strripos($link, self::LIVE_TICKET_URL) !== false) {
            $type = self::LIVE_TICKET;
            $id = (int)str_replace(self::LIVE_TICKET_URL, '', $link);
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
