<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\MailSigningKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;

interface MailSigningKeyRepositoryInterface
{
    /**
     * @throws RuntimeException when what the storage holds is not a key it wrote
     */
    public function current(Tenant $tenant, string $kind): ?MailSigningKey;

    /**
     * @throws RuntimeException when what the storage holds is not a key it wrote
     *
     * @return list<MailSigningKey> the current key of the format and the keys it replaced, newest first
     */
    public function ofKind(Tenant $tenant, string $kind): array;

    /**
     * @throws RuntimeException
     */
    public function save(MailSigningKey $key): void;

    /**
     * @throws RuntimeException
     */
    public function remove(MailSigningKey $key): void;
}
