<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use function array_filter;
use function array_values;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\CredentialRepositoryInterface;

final readonly class ActiveCredentials
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    /**
     * @template T of Credential
     *
     * @param class-string<T> $kind
     *
     * @return list<T> oldest first
     */
    public function of(User $user, string $kind = Credential::class): array
    {
        return array_values(array_filter(
            $this->credentials->findByUser($user, $kind),
            static fn (Credential $credential): bool => ! $credential instanceof RevocableCredentialInterface || ! $credential->isRevoked(),
        ));
    }
}
