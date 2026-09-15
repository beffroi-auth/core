<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Group;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class Group
{
    public function __construct(
        public readonly Uuid $id,
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
