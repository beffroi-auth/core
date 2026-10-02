<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use function array_any;
use Beffroi\Core\Application\Credential\ActiveCredentials;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\SecondFactorCredentialInterface;
use Beffroi\Core\Domain\Identity\User;

/**
 * Whether a password alone signs a user in (architecture §3.2): it does not while the user holds an
 * active credential that is a second factor (SecondFactorCredentialInterface), whatever its kind. The one
 * place an integration asks before it opens a session on a password; what it does with the answer, hold
 * the login until a code, is its own (the bundle, §5.4).
 */
final readonly class SecondFactorRequirement
{
    public function __construct(
        private ActiveCredentials $active,
    ) {
    }

    public function requiredFor(User $user): bool
    {
        return array_any(
            $this->active->of($user),
            static fn (Credential $credential): bool => $credential instanceof SecondFactorCredentialInterface,
        );
    }
}
