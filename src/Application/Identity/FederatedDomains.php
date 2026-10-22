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
 * The e-mail domains of a tenant that are federated to an upstream provider, and what follows from it
 * (decision 0029): **a domain declared for a provider signs in through that provider and through nothing
 * else**. The login page sends an address of such a domain to its provider, and every login of a local
 * credential refuses a person of it, so that a company that federates its domain keeps its accounts in its
 * directory, and a person it lets go is let go here too.
 *
 * Every domain is read by EmailDomain and by nothing else, the one rule the first sign-in through a provider
 * reads too (UpstreamProvider::answersFor()): the three places agree on an address in capitals, in Unicode or
 * with a final dot, because none of them reads one its own way.
 *
 * Nothing here reads a person who is not handed to it: which provider an address goes to is a function of its
 * domain and of the declarations of the tenant, so that the answer is the same whether somebody holds that
 * address or not.
 */
final readonly class FederatedDomains
{
    public function __construct(
        private UpstreamProvidersInterface $providers,
    ) {
    }

    /**
     * Whether the tenant declares an upstream provider, which is when the login page asks for an address first.
     */
    public function federates(Tenant $tenant): bool
    {
        return $this->providers->of($tenant) !== [];
    }

    /**
     * The provider the domain of what was typed is federated to. A value without an "@" is a username, of no
     * domain, and federated to nobody.
     *
     * @throws InvalidArgumentException when it holds an "@" and its domain is none the rule reads: refused, and
     *                                  never taken for an address of a domain nobody federates
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
     * Whether a local credential, a password, a passkey or a code, may sign that person in: not when one of their
     * addresses, their mailbox, verified or not, or their username when it holds an "@", is of a federated domain.
     * On a tenant that federates a domain, an address of theirs whose domain the rule does not read counts as one
     * of a federated domain: what cannot be read cannot be told apart from it.
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
