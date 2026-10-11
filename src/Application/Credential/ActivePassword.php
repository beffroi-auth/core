<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Identity\User;

/**
 * The password a user holds: one per user (architecture §3.1), replaced and never revoked. The one place
 * that says which one it is, for the login, for the page where a person replaces it, for the operator who
 * writes one, and for the firewall of the bundle.
 *
 * The counterpart of ActiveTotp, and built like it: what a person can still present is ActiveCredentials',
 * whatever the kind, and this says which of those is the password. A row read straight off the repository
 * would be the second place to hold that a password is never revoked, which is what ActiveCredentials
 * already states.
 */
final readonly class ActivePassword
{
    public function __construct(
        private ActiveCredentials $active,
    ) {
    }

    public function of(User $user): ?PasswordCredential
    {
        return $this->active->of($user, PasswordCredential::class)[0] ?? null;
    }
}
