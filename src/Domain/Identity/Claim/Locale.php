<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use function count;
use Beffroi\Core\Domain\Identity\User;

/**
 * The language the person reads, which this provider writes to them in when no request of theirs says one (UserLocale, issue #471).
 */
final readonly class Locale extends ProfileClaim
{
    public function name(): string
    {
        return 'locale';
    }

    public function of(User $user): ?string
    {
        return $user->profile->locale;
    }

    /**
     * @param list<string> $served the languages this deployment serves, in the order it serves them, which
     *                             only the integration knows (ServedLocales, architecture §5.7); a
     *                             deployment that names none offers something to type
     */
    public function __construct(
        private array $served = [],
    ) {
    }

    /**
     * A choice of the languages served, there being nothing else a person could read these pages in. A
     * deployment that serves one language offers no choice of it, there being nothing to choose.
     */
    public function field(): ClaimField
    {
        return count($this->served) > 1 ? ClaimField::oneOf($this->served) : ClaimField::text();
    }
}
