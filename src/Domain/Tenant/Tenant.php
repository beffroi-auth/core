<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Tenant;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\AuthenticationContextPolicy;
use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * An issuer, with its users, its clients, its keys and its "acr" vocabulary (decision 0010). Everything in
 * the core is scoped by a tenant.
 *
 * On every entity the identifier is private(set), not readonly: a mapper building lazy references assigns
 * it twice.
 */
final class Tenant
{
    public private(set) AuthenticationContextPolicy $authenticationContextPolicy;

    /**
     * @param AuthenticationContextPolicy|null $authenticationContextPolicy the default policy of decision 0010 when null
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly string $slug,
        public readonly Issuer $issuer,
        public private(set) string $name,
        public readonly DateTimeImmutable $createdAt,
        ?AuthenticationContextPolicy $authenticationContextPolicy = null,
    ) {
        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The tenant slug "%s" must be 1 to 63 lowercase letters, digits or hyphens, and must not start or end with a hyphen.',
                $slug,
            ));
        }
        $this->rename($name);
        $this->authenticationContextPolicy = $authenticationContextPolicy ?? AuthenticationContextPolicy::default();
    }

    /**
     * The tenant replaces its "acr" vocabulary with its relying parties, for all its clients (decision 0010).
     */
    public function adoptAuthenticationContextPolicy(AuthenticationContextPolicy $policy): void
    {
        $this->authenticationContextPolicy = $policy;
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
