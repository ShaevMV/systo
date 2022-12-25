<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Application;

use DomainException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\User\Account\Application\Create\CreatingNewAccountCommand;
use Tickets\User\Account\Application\Create\CreatingNewAccountCommandHandler;
use Tickets\User\Account\Application\Find\ByEmail\AccountFindByEmailQuery;
use Tickets\User\Account\Application\Find\ByEmail\AccountFindByEmailQueryHandler;
use Tickets\User\Account\Domain\Account;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;
use Tickets\User\Account\Response\IdAccountResponse;

final class AccountApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreatingNewAccountCommandHandler $accountCommandHandler,
        AccountFindByEmailQueryHandler $accountFindQueryHandler,
        private Bus $bus
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingNewAccountCommand::class => $accountCommandHandler
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            AccountFindByEmailQuery::class => $accountFindQueryHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function creatingOrGetAccountId(
        string $email
    ): Uuid
    {
        if($userInfoDto = $this->getUserByEmail($email)) {
            return $userInfoDto->getId();
        }

       $this->createNewAccount($email);

        if(is_null($userInfoDto = $this->getUserByEmail($email))) {
            throw new DomainException('Не получилось получить данные о созданом пользователе ' . $email);
        }

        return $userInfoDto->getId();
    }

    /**
     * @throws Throwable
     */
    private function createNewAccount(string $email): void
    {
        $password = Str::random(8);
        $accountDto = new AccountDto(
            $email,
            $password
        );

        $account = Account::creatingNewAccount(
            $accountDto->getId(),
            $email,
            $password
        );

        $this->commandBus->dispatch(new CreatingNewAccountCommand(
            $accountDto
        ));

        $this->bus::chain($account->pullDomainEvents())
            ->dispatch();

    }

    public function getUserByEmail(string $email): ?UserInfoDto
    {
        /** @var  UserInfoDto|null $resul */
        $resul = $this->queryBus->ask(new AccountFindByEmailQuery($email));

        return $resul;
    }
}
