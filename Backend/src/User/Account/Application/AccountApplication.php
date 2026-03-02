<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Throwable;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\User\Account\Application\ChanceRole\ChanceRoleCommand;
use Tickets\User\Account\Application\ChanceRole\ChanceRoleCommandHandler;
use Tickets\User\Account\Application\Create\CreatingNewAccountCommand;
use Tickets\User\Account\Application\Create\CreatingNewAccountCommandHandler;
use Tickets\User\Account\Application\Find\ByEmail\AccountFindByEmailQuery;
use Tickets\User\Account\Application\Find\ByEmail\AccountFindByEmailQueryHandler;
use Tickets\User\Account\Application\GetList\AccountGetListQuery;
use Tickets\User\Account\Application\GetList\AccountGetListQueryHandler;
use Tickets\User\Account\Domain\Account;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;
use Tickets\User\Account\Helpers\AccountRoleHelper;
use Tickets\User\Account\Response\AccountGetListResponse;

final class AccountApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreatingNewAccountCommandHandler $accountCommandHandler,
        ChanceRoleCommandHandler         $chanceRoleCommandHandler,

        AccountFindByEmailQueryHandler   $accountFindQueryHandler,
        AccountGetListQueryHandler       $accountGetListQueryHandler,

        private Bus                      $bus
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingNewAccountCommand::class => $accountCommandHandler,
            ChanceRoleCommand::class => $chanceRoleCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            AccountGetListQuery::class => $accountGetListQueryHandler,
            AccountFindByEmailQuery::class => $accountFindQueryHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function creatingOrGetAccountId(
        AccountDto $accountDto,
    ): Uuid
    {
        if ($userInfoDto = $this->getUserByEmail($accountDto->getEmail())) {
            return $userInfoDto->getId();
        }

        $this->createNewAccount(
            $accountDto,
        );

        return $accountDto->getId();
    }

    /**
     * Создать новый аккаунт
     *
     * @throws Throwable
     */
    public function createNewAccount(
        AccountDto $accountDto,
        ?string    $password = null
    ): void
    {
        $password = $password ?? Str::random(8);
        $account = Account::creatingNewAccount(
            $accountDto->getId(),
            $accountDto,
            $password
        );

        $this->commandBus->dispatch(new CreatingNewAccountCommand(
            $accountDto,
            $password
        ));

        $this->bus::chain($account->pullDomainEvents())
            ->dispatch();
    }

    /**
     * Найти пользователя по email
     *
     * @param string $email
     * @return UserInfoDto|null
     */
    public function getUserByEmail(string $email): ?UserInfoDto
    {
        /** @var  UserInfoDto|null $resul */
        $resul = $this->queryBus->ask(new AccountFindByEmailQuery($email));

        return $resul;
    }


    /**
     * Получить список всех пользователей
     *
     * @param AccountGetListQuery $accountGetListQuery
     * @return AccountGetListResponse
     */
    public function getList(AccountGetListQuery $accountGetListQuery): AccountGetListResponse
    {
        /** @var  AccountGetListResponse $resul */
        $resul = $this->queryBus->ask($accountGetListQuery);

        return $resul;
    }

    public function edit(Uuid $id, UserInfoDto $userInfoDto): bool
    {

        return true;
    }

    /**
     * @throws Throwable
     */
    public function chanceRole(Uuid $id, string $role): bool
    {
        $this->commandBus->dispatch(new ChanceRoleCommand($id, $role));
        return true;
    }
}
