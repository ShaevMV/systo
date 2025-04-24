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
    public const PARKING_TICKET = 'parking';
    public const PARKING_FREE_TICKET = 'parking_free';
    public const PARKING_CROSS_COUNTRY_TICKET = 'parking_cross-country';

    public const HUMAN_LIST = [
        self::ELECTRON_TICKET => 'Электронный',
        self::AUTO_TICKET => 'Автомобилишки',
        self::SPISOK_TICKET => 'Список',
        self::LIVE_TICKET => 'Живой',
        self::DRUG_TICKET => 'Френдли',
        self::PARKING_TICKET => 'Парковка гостевая',
        self::PARKING_FREE_TICKET => 'Парковка для своих',
        self::PARKING_CROSS_COUNTRY_TICKET => 'Парковка вездиход',
    ];

    public const PREFIX_LIST = [
        self::ELECTRON_TICKET => 'E-',
        self::SPISOK_TICKET => 'S',
        self::LIVE_TICKET => '',
        self::AUTO_TICKET => '',
        self::DRUG_TICKET => 'f',
        self::PARKING_TICKET => self::PARKING_TICKET,
        self::PARKING_FREE_TICKET => self::PARKING_FREE_TICKET,
        self::PARKING_CROSS_COUNTRY_TICKET => self::PARKING_CROSS_COUNTRY_TICKET,
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
        self::PARKING_TICKET => 'count_parking_tickets',
        self::PARKING_FREE_TICKET => 'count_parking_free_tickets',
        self::PARKING_CROSS_COUNTRY_TICKET => 'count_parking_cross-country_tickets',
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
            $id = $this->getOnlyNumber($link, 's');
        } elseif (strripos($link, 'f') !== false) {
            $type = self::DRUG_TICKET;
            $id = $this->getOnlyNumber($link, 'f');
        } elseif (strripos($link, self::PARKING_FREE_TICKET) !== false) {
            $type = self::PARKING_FREE_TICKET;
            $id = $this->getOnlyNumber($link, self::PARKING_FREE_TICKET);
        } elseif (strripos($link, self::PARKING_CROSS_COUNTRY_TICKET) !== false) {
            $type = self::PARKING_CROSS_COUNTRY_TICKET;
            $id = $this->getOnlyNumber($link, self::PARKING_CROSS_COUNTRY_TICKET);
        } elseif (strripos($link, self::PARKING_TICKET) !== false) {
            $type = self::PARKING_TICKET;
            $id = $this->getOnlyNumber($link, self::PARKING_TICKET);
        } elseif ($this->getOnlyNumber($link) == $link || strripos($link, self::LIVE_TICKET_URL) !== false) {
            $type = self::LIVE_TICKET;
            $id = (int)str_replace(self::LIVE_TICKET_URL, '', $link);
        } else {
            throw new InvalidArgumentException('Данная ссылка не опознана ' . $origLink);
        }

        return new SearchDto($type, $id);

    }

    private function getOnlyNumber(string $str, ?string $symbol = null): int
    {
        if($symbol !== null) {
            $start = mb_strripos($str, $symbol);
            $str = mb_substr($str, $start);
        }
        return (int)preg_replace("/[^,.0-9]/", '', $str);
    }
}
