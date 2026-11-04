<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
 */
final readonly class Locale extends ProfileClaim
{
    /**
     * @param array<string, string> $known each language's name in itself, by locale; none offers a line of text
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
     * Every language and not only those these pages are translated into: a relying party reads the claim.
     */
    public function field(): ClaimField
    {
        return $this->known === [] ? ClaimField::text() : ClaimField::oneOf($this->known);
    }
}
