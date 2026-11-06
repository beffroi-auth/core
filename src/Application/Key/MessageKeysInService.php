<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\MessageKeyProviderInterface;
use Beffroi\Core\Port\OpenableKeysInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;

/**
 * Each message key in service: a body waiting in a queue was sealed to the newest one of its time.
 */
final readonly class MessageKeysInService implements OpenableKeysInterface
{
    public function __construct(
        private TenantKeyRepositoryInterface $repository,
        private MessageKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        foreach ($this->repository->findInService($tenant) as $key) {
            if ($key->algorithm instanceof MessageSealingAlgorithmInterface) {
                $this->keys->messageKey($tenant, $key->kid);
            }
        }
    }
}
