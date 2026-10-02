<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Uid;

use Beffroi\Core\Port\UuidGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The generator of symfony/uid, which draws from the random source of the platform.
 */
final readonly class SymfonyUuidGenerator implements UuidGeneratorInterface
{
    public function timeOrdered(): Uuid
    {
        return Uuid::v7();
    }

    public function opaque(): Uuid
    {
        return Uuid::v4();
    }
}
