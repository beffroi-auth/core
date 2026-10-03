<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The language the person reads, which this provider writes to them in when no request of theirs says one (UserLocale, issue #471).
 */
final readonly class Locale extends ProfileClaim
{
    /**
     * @param array<string, string> $known the languages a person may say they read, each with its own name
     *                                     in itself, which only the integration knows (KnownLocales); a
     *                                     deployment that names none offers something to type
     */
    public function __construct(
        private array $known = [],
    ) {
    }

    public function name(): string
    {
        return 'locale';
    }

    public function of(User $user): ?string
    {
        return $user->profile->locale;
    }

    /**
     * A choice of the languages that exist and not of the ones these pages are translated into (issue
     * #479): "locale" is a claim of OIDC Core §5.1 and not a setting of this provider, so it is the
     * language the person reads, which a relying party consumes to dress its own pages. What language a
     * page of this provider is in degrades to one it has, which is read where it is read (ServedLocales).
     *
     * Several hundred names are typed into with the set suggested rather than chosen from a list, which
     * the field says of itself (`ClaimField::isPicked()`).
     */
    public function field(): ClaimField
    {
        return $this->known === [] ? ClaimField::text() : ClaimField::oneOf($this->known);
    }
}
