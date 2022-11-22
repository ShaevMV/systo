<?php

declare(strict_types = 1);

namespace Tickets\User\Application\Create;

use Illuminate\Support\Facades\Bus;
use Tickets\User\Domain\Account;
use Tickets\User\Dto\AccountDto;
use Tickets\User\Repositories\AccountInterface;

final class CreatingNewAccountCommandHandler
{
    public function __construct(
        private AccountInterface $account,
        private Bus $bus,
    ){
    }

    public function __invoke(CreatingNewAccountCommand $command): void
    {
        $accountDto = new AccountDto(
            $command->getEmail(),
            $command->getPassword()
        );
        if($this->account->create($accountDto)) {
            $account = Account::creatingNewAccount(
                $accountDto->getId(),
                $command->getEmail(),
                $command->getPassword()
            );
            $this->bus::chain($account->pullDomainEvents())
                ->dispatch();
        }
    }
}
