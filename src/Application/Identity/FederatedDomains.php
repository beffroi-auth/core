<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use function array_find;
use Beffroi\Core\Domain\Identity\EmailDomain;
use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\UpstreamProvidersInterface;
use function str_contains;

/**
 * Every domain is read by EmailDomain alone, so that every reading agrees on capitals, Unicode and a final dot.
 * The provider of an address depends on its domain alone, never on whether somebody holds it.
 */
final readonly class FederatedDomains
{
    public function __construct(
        private UpstreamProvidersInterface $providers,
    ) {
    }

    public function federates(Tenant $tenant): bool
    {
        return $this->providers->of($tenant) !== [];
    }

    /**
     * @throws InvalidArgumentException when it holds an "@" and a domain the rule cannot read
     */
    public function providerOf(Tenant $tenant, string $identifier): ?UpstreamProvider
    {
        if (! str_contains($identifier, '@')) {
            return null;
        }
        $domain = EmailDomain::ofAddress($identifier);

        return array_find($this->providers->of($tenant), static fn (UpstreamProvider $provider): bool => $provider->answersFor($domain));
    }

    /**
     * Every address counts, verified or not, and the username when it holds an "@". On a tenant that federates a
     * domain, an address the rule cannot read counts as federated.
     */
    public function admitsLocalCredentials(User $user): bool
    {
        if (! $this->federates($user->tenant)) {
            return true;
        }
        foreach ([$user->email?->value, $user->username] as $address) {
            try {
                if ($address !== null && $this->providerOf($user->tenant, $address) !== null) {
                    return false;
                }
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        return true;
    }
}
