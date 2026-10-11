<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationContextPolicy;
use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Domain\Session\SingleFactor;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * An issuer, with its users, its clients, its keys, its "acr" vocabulary (decision 0010) and the floor
 * under which none of its users signs in (issue #327). Everything in the core is scoped by a tenant.
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
     * What a login of this tenant proves at the least (issue #327), and therefore which credentials may
     * prove one and be enrolled at all (AuthenticationPredicateInterface::admits()). The single factor is every
     * login there is, so a tenant that names nothing behaves as this provider always did; a tenant that
     * names the phishing-resistant predicate signs its users in by passkey and by nothing else.
     *
     * It is not read from the configuration, where the "acr" vocabulary is: it is written on the row by
     * whoever administers the tenant, so that hardening is an act with a date and not a deployment
     * (TenantHardening).
     */
    public private(set) AuthenticationPredicateInterface $loginFloor;

    /**
     * @param AuthenticationContextPolicy|null $authenticationContextPolicy the default policy of decision 0010 when null
     * @param TenantLook|null $look nothing when null, which renders the pages as a deployment that declares no look
     * @param SelfServedClaims|null $selfServedClaims nothing when null, which lets a person change nothing of themselves
     * @param AuthenticationPredicateInterface|null $loginFloor the single factor when null, which admits every login this provider serves
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
        ?AuthenticationPredicateInterface $loginFloor = null,
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
        $this->loginFloor = $loginFloor ?? new SingleFactor();
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

    /**
     * The tenant names the floor of its logins (issue #327). Raising it closes a way in and is what the
     * hardening does with the credentials it forbids; naming the single factor again opens the kinds back
     * up and gives nobody a credential, the ones the hardening revoked having to be posed again.
     */
    public function adoptLoginFloor(AuthenticationPredicateInterface $floor): void
    {
        $this->loginFloor = $floor;
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
