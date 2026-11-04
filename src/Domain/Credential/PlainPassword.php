<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\PasswordRefusedException;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * NIST SP 800-63B §3.1.1.2
 *
 * The upper bound, in bytes, is where Symfony's password hashers stop (CVE-2013-1443).
 * The breach lookup is BreachedPasswordRefusal's, run by every use case that writes a password.
 */
final readonly class PlainPassword
{
    public const int MAXIMUM_BYTES = 4096;

    /**
     * The lowest rung: a higher one would be a length rule in disguise, which NIST SP 800-63B §3.1.1.2
     * asks verifiers not to impose.
     */
    public const int MINIMUM_STRENGTH = PasswordStrength::WEAK;

    /**
     * @throws PasswordRefusedException
     */
    public function __construct(
        #[SensitiveParameter]
        public string $value,
        PasswordLength $length,
    ) {
        if (! $length->reachedBy($value)) {
            throw new PasswordRefusedException(
                PasswordRefusal::TooShort,
                sprintf('A password is at least %d characters long.', $length->minimum),
            );
        }
        if (strlen($value) > self::MAXIMUM_BYTES) {
            throw new PasswordRefusedException(
                PasswordRefusal::TooLong,
                sprintf('A password is at most %d bytes long.', self::MAXIMUM_BYTES),
            );
        }
        if (! PasswordStrength::of($value)->reaches(self::MINIMUM_STRENGTH)) {
            throw new PasswordRefusedException(
                PasswordRefusal::TooWeak,
                'A password this short of length and of alphabet is guessed: make it longer, or several words.',
            );
        }
    }
}
