<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use function array_map;
use Beffroi\Core\Domain\Credential\BackupCode;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\PasswordAlgorithmsInterface;
use DateTimeImmutable;

final readonly class StoredBackupCodes
{
    private const string USED_AT = 'used_at';

    private StoredPasswordHash $hashes;

    public function __construct(PasswordAlgorithmsInterface $algorithms)
    {
        $this->hashes = new StoredPasswordHash($algorithms);
    }

    /**
     * @param list<BackupCode> $codes
     *
     * @return list<array<string, mixed>>
     */
    public function document(array $codes): array
    {
        return array_map(fn (BackupCode $code): array => [
            ...$this->hashes->document($code->hash),
            self::USED_AT => $code->usedAt?->getTimestamp(),
        ], $codes);
    }

    /**
     * @param list<mixed> $documents
     *
     * @throws InvalidArgumentException
     *
     * @return list<BackupCode>
     */
    public function of(array $documents): array
    {
        return array_map($this->code(...), $documents);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function code(mixed $document): BackupCode
    {
        $members = Document::of($document, 'backup code');
        $usedAt = $members->nullableInt(self::USED_AT);

        return new BackupCode($this->hashes->of($members->members), $usedAt === null ? null : new DateTimeImmutable('@' . $usedAt));
    }
}
