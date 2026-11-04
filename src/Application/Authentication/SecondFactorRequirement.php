<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use function array_any;
use Beffroi\Core\Application\Credential\ActiveCredentials;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\SecondFactorCredentialInterface;
use Beffroi\Core\Domain\Identity\User;

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
