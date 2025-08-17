<?php

declare(strict_types=1);

namespace Tickets\Billing\ValueObject;

class DeviceValueObject
{
    public const DESKTOP = 'desktop';
    public const ANDROID = 'android';
    public const IOS = 'ios';

    public function __construct(
        private string $device = 'desktop',
    )
    {
    }

    public function isDesktop():bool
    {
        return $this->device === self::DESKTOP;
    }

    public function isAndroid():bool
    {
        return $this->device === self::ANDROID;
    }

    public function isIOS():bool
    {
        return $this->device === self::IOS;
    }

    public function getDevice(): string
    {
        return $this->device;
    }
}
