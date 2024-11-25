<?php

declare(strict_types=1);

namespace Tests\Unit\AccountApplication;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

class AccountApplicationTest extends TestCase
{
    use DatabaseTransactions;

    private AccountApplication $accountApplication;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var AccountApplication $accountApplication */
        $accountApplication = $this->app->get(AccountApplication::class);
        $this->accountApplication = $accountApplication;
    }


    /**
     * @throws Throwable
     */
    public function test_it_create_new_account(): void
    {
        $accountDto = AccountDto::fromState([
            'email' => 'email@test.ru',
            'phone' => '+79516486456',
            'city' => 'SPB'
        ]);

        $idAfterCreate = $this->accountApplication
            ->creatingOrGetAccountId(
                $accountDto
            );

        self::assertTrue($accountDto->getId()->equals($idAfterCreate));
    }

    public function test_it_correct_login(): void
    {
        $token = auth()->attempt(
            [
                'email' => UserSeeder::EMAIL_ADMIN,
                'password' => UserSeeder::PASSWORD_ADMIN
            ], true);

        self::assertNotFalse($token);


        $token = auth()->attempt(
            [
                'email' => UserSeeder::EMAIL_ADMIN,
                'password' => UserSeeder::PASSWORD_ADMIN.'1564',
            ], true);

        self::assertFalse($token);
    }
}
