<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use function array_any;
use function array_key_exists;
use function array_values;
use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function sprintf;

/**
 * An upstream provider as the deployment declares it, which is what a sign-in through it is given: its
 * name, the one its linked identities carry (`LinkedIdentity::$provider`) and the source of a person it
 * creates names (`UserSource::$provider`), the e-mail domains it answers for, and those of the claims of
 * the profile of OIDC Core §5.1 it fills.
 *
 * **It answers for its domains and for nothing else** (decision 0029): a provider creates a person only of a
 * domain it declares, which is what makes a person it creates safe to create at all. A domain is the one
 * reading of EmailDomain, in lower case and in its A-label, compared exactly, so a subdomain is not its
 * parent: a provider that answers for `eu.acme.example` declares it.
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
     * @var list<EmailDomain> each once, in the order they were declared
     */
    public array $domains;

    /**
     * @var list<string> the names of OIDC Core §5.1 of the claims of the profile it fills, each once
     */
    public array $claims;

    /**
     * @param list<EmailDomain> $domains the e-mail domains it answers for
     * @param list<string> $claims the claims of the profile it fills, by their names in OIDC Core §5.1
     *
     * @throws InvalidArgumentException when it is unnamed, or a claim is none of the profile
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
            $folded[$domain->value] ??= $domain;
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
        $this->domains = array_values($folded);
        $this->claims = $filled;
    }

    /**
     * @param EmailDomain $domain the domain of an address, as EmailDomain::ofAddress() read it
     */
    public function answersFor(EmailDomain $domain): bool
    {
        return array_any($this->domains, static fn (EmailDomain $declared): bool => $declared->equals($domain));
    }

    /**
     * The source of a person as this provider holds it from what it said at a sign-in: it names this
     * provider, and holds the address and those of the claims of the profile it fills that it said, which a
     * manual description then leaves to it. A claim it fills and did not say, it holds no longer.
     *
     * @param array<string, string> $said what the provider said, by the names of OIDC Core §5.1
     */
    public function source(array $said): UserSource
    {
        $held = [self::EMAIL];
        foreach ($this->claims as $claim) {
            if (array_key_exists($claim, $said)) {
                $held[] = $claim;
            }
        }

        return UserSource::upstream($this->name, $held);
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
