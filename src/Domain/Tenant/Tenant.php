<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationContextPolicy;
use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Domain\Session\SingleFactor;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * The identifier is private(set), not readonly: a mapper building lazy references assigns it twice.
 */
final class Tenant implements ClassifiedFieldsInterface
{
    public private(set) AuthenticationContextPolicy $authenticationContextPolicy;

    public private(set) TenantLook $look;

    public private(set) SelfServedClaims $selfServedClaims;

    public private(set) AuthenticationPredicateInterface $loginFloor;

    public private(set) PasskeyAttestation $passkeyAttestation;

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
        ?PasskeyAttestation $passkeyAttestation = null,
    ) {
        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $slug) !== 1) {
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
        $this->passkeyAttestation = $passkeyAttestation ?? PasskeyAttestation::none();
    }

    public function adoptAuthenticationContextPolicy(AuthenticationContextPolicy $policy): void
    {
        $this->authenticationContextPolicy = $policy;
    }

    public function adoptLook(TenantLook $look): void
    {
        $this->look = $look;
    }

    public function adoptSelfServedClaims(SelfServedClaims $claims): void
    {
        $this->selfServedClaims = $claims;
    }

    public function adoptLoginFloor(AuthenticationPredicateInterface $floor): void
    {
        $this->loginFloor = $floor;
    }

    public function adoptPasskeyAttestation(PasskeyAttestation $attestation): void
    {
        $this->passkeyAttestation = $attestation;
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

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'slug' => new NoPersonalDatum(Reason::THE_TENANT),
            'issuer' => new NoPersonalDatum(Reason::THE_TENANT),
            'name' => new NoPersonalDatum(Reason::THE_TENANT),
            'authenticationContextPolicy' => new NoPersonalDatum('the authentication classes the tenant announces, which name predicates and no person'),
            'loginFloor' =>
                new NoPersonalDatum('the predicate every login of this tenant satisfies at the least (issue #327): one of the four names this provider defines, and nothing of anybody who signs in'),
            'look' => new NoPersonalDatum('the logo and the two colours a tenant gives its pages (issue #449), beside the name it is already named by: what every page hands to every visitor before anybody has signed in'),
            'passkeyAttestation' =>
                new NoPersonalDatum('what the enrolment of a passkey asks of its authenticator in this tenant: the name of a policy, certificates of authorities and AAGUIDs of models, nothing of anybody'),
            'selfServedClaims' =>
                new NoPersonalDatum('the names of the claims the people of this tenant may change of themselves (issue #452): names of claims, never a value and never a person'),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
