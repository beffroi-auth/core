<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use function array_map;
use Beffroi\Core\Domain\Credential\BackupCode;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\RandomnessInterface;
use SensitiveParameter;

final readonly class BackupCodeSet
{
    public function __construct(
        private RandomnessInterface $randomness,
        private PasswordHasherInterface $hasher,
    ) {
    }

    /**
     * Shown to the person once and kept nowhere.
     *
     * @return list<string>
     */
    public function draw(): array
    {
        $spelled = [];
        for ($code = 0; $code < TotpCredential::BACKUP_CODES; ++$code) {
            $spelled[] = BackupCode::spelledBy($this->randomness->bytes(BackupCode::LENGTH));
        }

        return $spelled;
    }

    /**
     * @param list<string> $spelled
     *
     * @return list<BackupCode>
     */
    public function hashed(#[SensitiveParameter] array $spelled): array
    {
        return array_map(fn (string $code): BackupCode => new BackupCode($this->hasher->hash(BackupCode::normalize($code))), $spelled);
    }
}
