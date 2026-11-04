<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\Device;

interface UserAgentParserInterface
{
    public function parse(string $userAgent): ?Device;
}
