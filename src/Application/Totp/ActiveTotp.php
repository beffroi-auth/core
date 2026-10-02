<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Application\Credential\ActiveCredentials;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Identity\User;

/**
 * The TOTP a user holds and may use: one active per user (architecture §3.1), the revoked ones staying
 * in the rows for the audit trail. The one place that tells the active one from the others, for the
 * authentication, the enrolment that refuses a second one, and the pages of the account.
 */
final readonly class ActiveTotp
{
    public function __construct(
        private ActiveCredentials $active,
    ) {
    }

    public function of(User $user): ?TotpCredential
    {
        return $this->active->of($user, TotpCredential::class)[0] ?? null;
    }
}
