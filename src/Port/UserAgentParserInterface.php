<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Session\Device;

/**
 * Turns a user agent string into a device description. Called asynchronously, never on the request path.
 */
interface UserAgentParserInterface
{
    public function parse(string $userAgent): ?Device;
}
