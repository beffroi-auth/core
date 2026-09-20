<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Totp;

use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Port\CredentialRepositoryInterface;

/**
 * The TOTP a user holds and may use: one active per user (architecture §3.1), the revoked ones staying
 * in the rows for the audit trail. The one place that tells the active one from the others, for the
 * authentication, the enrolment that refuses a second one, and the pages of the account.
 */
final readonly class ActiveTotp
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    public function of(User $user): ?TotpCredential
    {
        foreach ($this->credentials->findByUser($user, TotpCredential::class) as $credential) {
            if (! $credential->isRevoked()) {
                return $credential;
            }
        }

        return null;
    }
}
