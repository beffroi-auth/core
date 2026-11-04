<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use DateTimeImmutable;
use Beffroi\Core\Exception\LogicException;
use function implode;
use function ord;
use function preg_replace;
use SensitiveParameter;
use function str_split;
use function strtoupper;

final readonly class BackupCode
{
    /**
     * RFC 4648 §6
     */
    public const string ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const int LENGTH = 10;

    public function __construct(
        public PasswordHash $hash,
        public ?DateTimeImmutable $usedAt = null,
    ) {
    }

    /**
     * The low five bits of each byte pick a character: no bias, 256 being a multiple of 32.
     *
     * @param string $bytes LENGTH bytes from RandomnessInterface
     */
    public static function spelledBy(#[SensitiveParameter] string $bytes): string
    {
        $characters = '';
        foreach (str_split($bytes) as $byte) {
            $characters .= self::ALPHABET[ord($byte) & 0x1F];
        }

        return implode('-', str_split($characters, 5));
    }

    public static function normalize(#[SensitiveParameter] string $typed): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $typed));
    }

    public function isSpent(): bool
    {
        return $this->usedAt !== null;
    }

    public function spend(DateTimeImmutable $now): self
    {
        if ($this->usedAt !== null) {
            throw new LogicException('A backup code is spent once.');
        }

        return new self($this->hash, $now);
    }
}
