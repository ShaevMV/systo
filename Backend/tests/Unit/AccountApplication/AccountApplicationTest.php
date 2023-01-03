<?php

namespace Tests\Unit\AccountApplication;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\User\Account\Application\AccountApplication;

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
        self::assertNotEmpty($this->accountApplication
            ->creatingOrGetAccountId('shaevMV3@gmail.com'));
    }
}
