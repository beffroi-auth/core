<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class Group
{
    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public private(set) string $name,
        public readonly GroupSource $source,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->rename($name);
    }

    public function rename(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A group name must not be empty.');
        }
        $this->name = $name;
    }
}
