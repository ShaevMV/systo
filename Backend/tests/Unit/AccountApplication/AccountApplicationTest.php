<?php

namespace Tests\Unit\AccountApplication;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\User\Application\AccountApplication;

class AccountApplicationTest extends TestCase
{
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


    public function test_it_create_new_account(): void
    {
        self::assertNotEmpty($this->accountApplication
            ->creatingOrGetAccount('shaevMV3@gmail.com'));
    }
}
