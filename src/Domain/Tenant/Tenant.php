<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Tenant;

use DateTimeImmutable;
use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * An issuer, with its users, its clients and its keys. Everything in the core is scoped by a tenant.
 *
 * On every entity the identifier is private(set), not readonly: a mapper building lazy references assigns
 * it twice.
 */
final class Tenant
{
    public function __construct(
        public private(set) Uuid $id,
        public readonly string $slug,
        public readonly Issuer $issuer,
        public private(set) string $name,
        public readonly DateTimeImmutable $createdAt,
    ) {
        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('The tenant slug "%s" must be 1 to 63 lowercase letters, digits or hyphens, and must not start or end with a hyphen.', $slug));
        }
        $this->rename($name);
    }

    public function rename(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('The tenant name must not be empty.');
        }
        $this->name = $name;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
