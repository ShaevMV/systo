<?php

declare(strict_types=1);

namespace Tickets\History\Domain;

final class ActorType
{
    public const USER    = 'user';
    public const SYSTEM  = 'system';
    public const ARTISAN = 'artisan';
}
