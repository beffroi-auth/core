<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Key\MailSigningKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\MailSigningKeyRepositoryInterface;
use function usort;

final class InMemoryMailSigningKeyRepository implements MailSigningKeyRepositoryInterface
{
    /**
     * @var array<string, MailSigningKey> by tenant, format and fingerprint
     */
    private array $keys = [];

    public function current(Tenant $tenant, string $kind): ?MailSigningKey
    {
        foreach ($this->ofKind($tenant, $kind) as $key) {
            if ($key->isCurrent()) {
                return $key;
            }
        }

        return null;
    }

    public function ofKind(Tenant $tenant, string $kind): array
    {
        $theirs = [];
        foreach ($this->keys as $key) {
            if ($key->tenant->equals($tenant) && $key->kind === $kind) {
                $theirs[] = $key;
            }
        }
        usort($theirs, static fn (MailSigningKey $a, MailSigningKey $b): int => $b->setAt <=> $a->setAt);

        return $theirs;
    }

    public function save(MailSigningKey $key): void
    {
        $this->keys[self::name($key)] = $key;
    }

    public function remove(MailSigningKey $key): void
    {
        unset($this->keys[self::name($key)]);
    }

    private static function name(MailSigningKey $key): string
    {
        return $key->tenant->id->toRfc4122() . '/' . $key->kind . '/' . $key->fingerprint;
    }
}
