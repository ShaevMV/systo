<?php

declare(strict_types = 1);

namespace Tickets\User\Application\Create;

use Tickets\Shared\Domain\Bus\Event\DomainEventSubscriber;
use Tickets\User\Domain\AccountNewCreatingDomainEvent;

final class CreatingNewAccountDomainEventSubscriber implements DomainEventSubscriber
{
    public function __construct()
    {
    }


    public static function subscribedTo(): array
    {
        return [
            AccountNewCreatingDomainEvent::class,
        ];
    }

    public function __invoke(AccountNewCreatingDomainEvent $domainEvent)
    {

    }
}
