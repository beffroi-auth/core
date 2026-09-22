<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Credential\SecondFactorCredentialInterface;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Port\CredentialRepositoryInterface;

/**
 * Whether a password alone signs a user in (architecture §3.2): it does not while the user holds an
 * active credential that is a second factor (SecondFactorCredentialInterface), whatever its kind. The one
 * place an integration asks before it opens a session on a password; what it does with the answer, hold
 * the login until a code, is its own (the bundle, §5.4).
 */
final readonly class SecondFactorRequirement
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    public function requiredFor(User $user): bool
    {
        return array_any(
            $this->credentials->findByUser($user),
            fn ($credential) => $credential instanceof SecondFactorCredentialInterface && ! $credential->isRevoked(),
        );
    }
}
