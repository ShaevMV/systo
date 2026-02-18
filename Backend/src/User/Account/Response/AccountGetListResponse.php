<?php

declare(strict_types=1);

namespace Tickets\User\Account\Response;

use Nette\Utils\JsonException;
use Shared\Domain\Bus\Query\Response;
use Tickets\User\Account\Dto\UserInfoDto;

class AccountGetListResponse implements Response
{
    /**
     * @param UserInfoDto[] $accountList
     */
    public function __construct(
        private array $accountList
    )
    {
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->accountList as $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }


}
