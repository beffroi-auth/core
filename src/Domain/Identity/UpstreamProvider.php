<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use function array_key_exists;
use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * An upstream provider as the deployment declares it, which is what a sign-in through it is given: its
 * name, the one its linked identities carry (`LinkedIdentity::$provider`) and the source of a person it
 * creates names (`UserSource::$provider`), the e-mail domains it answers for, and those of the claims of
 * the profile of OIDC Core §5.1 it fills.
 *
 * **It answers for its domains and for nothing else** (decision 0029): a provider creates a person only of a
 * domain it declares, which is what makes a person it creates safe to create at all. A domain is compared
 * exactly and without regard to ASCII case (RFC 4343 §3), so a subdomain is not its parent: a provider that
 * answers for `eu.acme.example` declares it.
 *
 * Protocol-neutral, as the rest of the identity model: a SAML identity provider, an OpenID Connect issuer
 * of a federation and a SCIM client are each one of these, a name, domains and claims.
 */
final readonly class UpstreamProvider
{
    /**
     * The claim a provider holds of every person it creates, beside those of the profile it fills: the
     * address its domains are read from.
     */
    public const string EMAIL = 'email';

    /**
     * @var list<string> each once, in lowercase, in the order they were declared
     */
    public array $domains;

    /**
     * @var list<string> the names of OIDC Core §5.1 of the claims of the profile it fills, each once
     */
    public array $claims;

    /**
     * @param list<string> $domains the e-mail domains it answers for
     * @param list<string> $claims the claims of the profile it fills, by their names in OIDC Core §5.1
     *
     * @throws InvalidArgumentException when it is unnamed, a domain is empty, or a claim is none of the profile
     */
    public function __construct(
        public string $name,
        array $domains,
        array $claims = [],
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('An upstream provider is named.');
        }
        $folded = [];
        foreach ($domains as $domain) {
            if ($domain === '') {
                throw new InvalidArgumentException(sprintf('A domain the upstream provider "%s" answers for is named.', $name));
            }
            $domain = strtolower($domain);
            if (! in_array($domain, $folded, true)) {
                $folded[] = $domain;
            }
        }
        $profile = Profile::empty()->claims();
        $filled = [];
        foreach ($claims as $claim) {
            if (! array_key_exists($claim, $profile)) {
                throw new InvalidArgumentException(sprintf('The upstream provider "%s" fills "%s", which is no claim of the profile.', $name, $claim));
            }
            if (! in_array($claim, $filled, true)) {
                $filled[] = $claim;
            }
        }
        $this->domains = $folded;
        $this->claims = $filled;
    }

    public function answersFor(EmailAddress $email): bool
    {
        return in_array($email->domain(), $this->domains, true);
    }

    /**
     * The source of a person this provider creates: it names this provider, and holds the address and the
     * claims of the profile it fills, which a manual description then leaves to it.
     */
    public function source(): UserSource
    {
        return UserSource::upstream($this->name, [self::EMAIL, ...$this->claims]);
    }

    /**
     * Whether this provider is the source of that person, which is what lets it write what that source
     * holds. A person a console created, or another provider, may have an identity at this one all the
     * same, which an import linked, and this one writes nothing of them.
     */
    public function isTheSourceOf(User $user): bool
    {
        return $user->source->provider === $this->name;
    }
}
