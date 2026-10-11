<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use function array_map;
use Beffroi\Core\Domain\Credential\BackupCode;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\RandomnessInterface;
use SensitiveParameter;

/**
 * The set of backup codes a TOTP is handed: how many of them there are, where their bytes come from and
 * what the credential holds of them. The enrolment draws one and the renewal draws another, and the loop
 * that draws them and the map that hashes them are written here once.
 *
 * **The drawing and the hashing are two acts because the enrolment holds them apart**: a set is drawn
 * before any credential exists, shown to whoever asked, and hashed only once a first code has proved the
 * authenticator, so an enrolment abandoned in between has cost no hashing and left nothing. A renewal
 * does both at once, having nothing to wait for.
 */
final readonly class BackupCodeSet
{
    public function __construct(
        private RandomnessInterface $randomness,
        private PasswordHasherInterface $hasher,
    ) {
    }

    /**
     * The codes as they are spelled to the person, shown this once and kept nowhere, each one drawn where
     * every secret of this provider is.
     *
     * @return list<string> exactly the number a credential holds
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
     * What the credential holds of them: the hash of each one, as a password is.
     *
     * @param list<string> $spelled the codes draw() answered, in their order
     *
     * @return list<BackupCode>
     */
    public function hashed(#[SensitiveParameter] array $spelled): array
    {
        return array_map(fn (string $code): BackupCode => new BackupCode($this->hasher->hash(BackupCode::normalize($code))), $spelled);
    }
}
