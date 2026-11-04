<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function count;
use function count_chars;
use function log;
use SensitiveParameter;
use function strlen;

/**
 * Counted over bytes, as password-strength.js counts, so the page and the server agree.
 * The rungs are those of symfony/validator's PasswordStrength.
 */
final readonly class PasswordStrength
{
    public const int NOTHING = 0;

    public const int WEAK = 1;

    public const int MEDIUM = 2;

    public const int STRONG = 3;

    public const int VERY_STRONG = 4;

    /**
     * @var array<int, int> bits by rung, strongest first
     */
    private const array BITS_PER_RUNG = [
        self::VERY_STRONG => 120,
        self::STRONG => 100,
        self::MEDIUM => 80,
        self::WEAK => 60,
    ];

    private const int CONTROL = 33;

    private const int DIGITS = 10;

    private const int LETTERS = 26;

    private const int SYMBOLS = 33;

    private const int BEYOND_ASCII = 128;

    private function __construct(
        public float $bits,
        public int $rung,
    ) {
    }

    public static function of(#[SensitiveParameter] string $password): self
    {
        $bits = self::bitsOf($password);

        foreach (self::BITS_PER_RUNG as $rung => $required) {
            if ($bits >= $required) {
                return new self($bits, $rung);
            }
        }

        return new self($bits, self::NOTHING);
    }

    public function reaches(int $rung): bool
    {
        return $this->rung >= $rung;
    }

    /**
     * A repeated byte pays only for the distinct bytes, so "aaaaaaaaaaaa" is not twelve characters of entropy.
     */
    private static function bitsOf(#[SensitiveParameter] string $password): float
    {
        $length = strlen($password);
        if ($length === 0) {
            return 0.0;
        }
        $bytes = count_chars($password, 1);
        $distinct = count($bytes);

        $control = $digits = $capitals = $letters = $symbols = $beyond = 0;
        foreach ($bytes as $byte => $_count) {
            match (true) {
                $byte < 32 || $byte === 127 => $control = self::CONTROL,
                $byte >= 48 && $byte <= 57 => $digits = self::DIGITS,
                $byte >= 65 && $byte <= 90 => $capitals = self::LETTERS,
                $byte >= 97 && $byte <= 122 => $letters = self::LETTERS,
                $byte >= 128 => $beyond = self::BEYOND_ASCII,
                default => $symbols = self::SYMBOLS,
            };
        }
        $pool = $control + $digits + $capitals + $letters + $symbols + $beyond;

        return ($distinct * log($pool, 2)) + (($length - $distinct) * log($distinct, 2));
    }
}
