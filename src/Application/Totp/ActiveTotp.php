<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Application\Credential\ActiveCredentials;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Identity\User;

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
