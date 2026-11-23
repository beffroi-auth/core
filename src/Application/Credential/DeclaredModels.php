<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use function array_values;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\ModelDeclaringCredentialInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The models a list of credentials can be narrowed to (CredentialQuestion::$model).
 */
final readonly class DeclaredModels
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    /**
     * @param class-string<Credential> $kind
     *
     * @return list<Uuid> the AAGUID of every model a credential of that kind declares, each once, in the order the first credential of each was enrolled
     */
    public function of(User $user, string $kind = Credential::class): array
    {
        $models = [];
        foreach ($this->credentials->findByUser($user, $kind) as $credential) {
            if ($credential instanceof ModelDeclaringCredentialInterface) {
                $models[$credential->aaguid->toRfc4122()] ??= $credential->aaguid;
            }
        }

        return array_values($models);
    }
}
