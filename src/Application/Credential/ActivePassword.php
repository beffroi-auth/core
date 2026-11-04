<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Identity\User;

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
