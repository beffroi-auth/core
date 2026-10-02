<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use function array_filter;
use function array_values;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\CredentialRepositoryInterface;

/**
 * The credentials a user can still authenticate with: the repository keeps a revoked one for the audit
 * trail (CredentialRevocation), and every login that asks what a user holds means the ones that are left.
 * The one place that tells the two apart, so that a kind which cannot be revoked at all (a password, which
 * is replaced) needs no exception and no caller repeats the question.
 */
final readonly class ActiveCredentials
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    /**
     * Oldest first, as the repository lists them.
     *
     * @template T of Credential
     *
     * @param class-string<T> $kind
     *
     * @return list<T>
     */
    public function of(User $user, string $kind = Credential::class): array
    {
        return array_values(array_filter(
            $this->credentials->findByUser($user, $kind),
            static fn (Credential $credential): bool => ! $credential instanceof RevocableCredentialInterface || ! $credential->isRevoked(),
        ));
    }
}
