<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Null;

use Beffroi\Core\Domain\Session\Device;
use Beffroi\Core\Port\UserAgentParserInterface;

final class NullUserAgentParser implements UserAgentParserInterface
{
    public function parse(string $userAgent): ?Device
    {
        return null;
    }
}
