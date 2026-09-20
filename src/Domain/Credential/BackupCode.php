<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Exception\LogicException;
use function implode;
use function ord;
use function preg_replace;
use function random_bytes;
use SensitiveParameter;
use function str_split;
use function strtoupper;

/**
 * One of the codes a user falls back on when the authenticator of their TOTP is out of reach: a hash of
 * the code, typed like a password hash, and the instant it was spent, because each one is accepted once.
 * The code itself is shown at enrolment and kept nowhere.
 */
final readonly class BackupCode
{
    /**
     * The alphabet of RFC 4648 §6, which has no character a reader mistakes for another; ten of them are
     * fifty bits of entropy.
     */
    public const string ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const int LENGTH = 10;

    public function __construct(
        public PasswordHash $hash,
        public ?DateTimeImmutable $usedAt = null,
    ) {
    }

    /**
     * A fresh code in clear, in two groups of five for the eye ("K7Q2M-X9PL4"); what the user writes down.
     * One random byte per character, its low five bits picking one of the thirty-two: no bias, 256 being a
     * multiple of 32.
     */
    public static function generate(): string
    {
        $characters = '';
        foreach (str_split(random_bytes(self::LENGTH)) as $byte) {
            $characters .= self::ALPHABET[ord($byte) & 0x1F];
        }

        return implode('-', str_split($characters, 5));
    }

    /**
     * What the user typed, as it is compared: upper case, the separators and the spaces dropped.
     */
    public static function normalize(#[SensitiveParameter] string $typed): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $typed));
    }

    public function isSpent(): bool
    {
        return $this->usedAt !== null;
    }

    /**
     * The same code, spent: a value object, so the credential replaces its entry.
     */
    public function spend(DateTimeImmutable $now): self
    {
        if ($this->usedAt !== null) {
            throw new LogicException('A backup code is spent once.');
        }

        return new self($this->hash, $now);
    }
}
