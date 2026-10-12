<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function count;
use function count_chars;
use function log;
use SensitiveParameter;
use function strlen;

/**
 * How hard a password is to guess, in bits, and the rung that number falls on (NIST SP 800-63B §3.1.1.2).
 *
 * **It is an estimate of entropy and never a composition rule.** The same section asks a verifier not to
 * impose one: a password is not refused for holding no digit and no capital, it is refused for being
 * short of alphabet and short of length at once, which is what this counts. The alphabets the password
 * actually draws from give the size of the pool, the distinct characters give what each position is
 * worth, and a character written twice is worth less than the first one, so "aaaaaaaaaaaa" does not pass
 * for twelve characters of entropy.
 *
 * **It is counted over the bytes of the password**, which is what the script of the login page counts
 * too (`password-strength.js`): the two agree on what they can, and the server is what pronounces.
 *
 * The rungs are the five of symfony/validator's `PasswordStrength`, which is also what the meter of
 * symfony/ux#1179 drew, so a deployment that already told its people what "medium" means does not have to
 * tell them something else here.
 */
final readonly class PasswordStrength
{
    /**
     * Nothing of a password: fewer than sixty bits, which is a word of a dictionary with a digit behind
     * it.
     */
    public const int NOTHING = 0;

    public const int WEAK = 1;

    public const int MEDIUM = 2;

    public const int STRONG = 3;

    public const int VERY_STRONG = 4;

    /**
     * What each rung costs, in bits, from the strongest down: the first one the estimate reaches is the
     * rung it is on.
     *
     * @var array<int, int>
     */
    private const array BITS_PER_RUNG = [
        self::VERY_STRONG => 120,
        self::STRONG => 100,
        self::MEDIUM => 80,
        self::WEAK => 60,
    ];

    /**
     * The alphabets a byte can belong to and what each one adds to the pool: the twenty-six letters count
     * once for the capitals and once for the rest. One byte belongs to one alphabet, and a password that
     * draws from an alphabet at all pays for the whole of it, which is what makes this an estimate of the
     * search space and not a count of the characters that are there.
     */
    private const int CONTROL = 33;

    private const int DIGITS = 10;

    private const int LETTERS = 26;

    private const int SYMBOLS = 33;

    private const int BEYOND_ASCII = 128;

    private function __construct(
        /**
         * The size of the search space, in bits.
         */
        public float $bits,
        /**
         * Which of the five rungs the estimate falls on.
         */
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

    /**
     * Whether this estimate reaches the rung asked of it.
     */
    public function reaches(int $rung): bool
    {
        return $this->rung >= $rung;
    }

    /**
     * The distinct bytes pay for the pool they are drawn from, the repeated ones for the alphabet the
     * password itself turned out to use, which is smaller.
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
