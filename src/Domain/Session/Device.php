<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

/**
 * What a user agent string says about the browser and the machine behind a request, as a parser understood
 * it. Kept coarse on purpose: enough for a user to recognise "Firefox on Linux, desktop" in a session list.
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
