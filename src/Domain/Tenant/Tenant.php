<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationContextPolicy;
use Beffroi\Core\Exception\InvalidArgumentException;
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
     * What the pages show of this tenant besides its name (issue #449); nothing, for a tenant that
     * declared none.
     */
    public private(set) TenantLook $look;

    /**
     * What a person may change of themselves here (issue #452); nothing, until this tenant says otherwise.
     */
    public private(set) SelfServedClaims $selfServedClaims;

    /**
     * @param AuthenticationContextPolicy|null $authenticationContextPolicy the default policy of decision 0010 when null
     * @param TenantLook|null $look nothing when null, which renders the pages as a deployment that declares no look
     * @param SelfServedClaims|null $selfServedClaims nothing when null, which lets a person change nothing of themselves
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly string $slug,
        public readonly Issuer $issuer,
        public private(set) string $name,
        public readonly DateTimeImmutable $createdAt,
        ?AuthenticationContextPolicy $authenticationContextPolicy = null,
        ?TenantLook $look = null,
        ?SelfServedClaims $selfServedClaims = null,
    ) {
        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The tenant slug "%s" must be 1 to 63 lowercase letters, digits or hyphens, and must not start or end with a hyphen.',
                $slug,
            ));
        }
        $this->rename($name);
        $this->authenticationContextPolicy = $authenticationContextPolicy ?? AuthenticationContextPolicy::default();
        $this->look = $look ?? TenantLook::none();
        $this->selfServedClaims = $selfServedClaims ?? SelfServedClaims::none();
    }

    /**
     * The tenant replaces its "acr" vocabulary with its relying parties, for all its clients (decision 0010).
     */
    public function adoptAuthenticationContextPolicy(AuthenticationContextPolicy $policy): void
    {
        $this->authenticationContextPolicy = $policy;
    }

    /**
     * The tenant replaces what its pages show of it, its logo and its colours (issue #449); its name is
     * renamed, being what everything else of the provider calls it.
     */
    public function adoptLook(TenantLook $look): void
    {
        $this->look = $look;
    }

    /**
     * The tenant replaces what its people may change of themselves (issue #452), as it replaces its "acr"
     * vocabulary and its look: whole, and never one claim at a time.
     */
    public function adoptSelfServedClaims(SelfServedClaims $claims): void
    {
        $this->selfServedClaims = $claims;
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
