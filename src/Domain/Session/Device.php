<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

/**
 * What a user agent string says, coarse enough for a session list ("Firefox on Linux, desktop").
 */
final readonly class Device
{
    public function __construct(
        public DeviceType $type = DeviceType::Unknown,
        public ?string $browser = null,
        public ?string $browserVersion = null,
        public ?string $platform = null,
        public ?string $platformVersion = null,
    ) {
    }
}
