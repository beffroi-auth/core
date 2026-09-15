<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Null;

use Heidi\Core\Domain\Session\Device;
use Heidi\Core\Port\UserAgentParserInterface;

final class NullUserAgentParser implements UserAgentParserInterface
{
    public function parse(string $userAgent): ?Device
    {
        return null;
    }
}
