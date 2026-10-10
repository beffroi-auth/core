<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function mb_strlen;
use Beffroi\Core\Exception\PasswordRefusedException;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * A password as the user typed it, in memory only, on its way to a hasher. What a password has to be
 * before anything hashes it is written here and nowhere else: whoever sets one holds one of these, so a
 * path that sets a password cannot be the one that forgot the rule.
 *
 * The lower bound is NIST SP 800-63B §5.1.1.2, counted in characters because that is what a user typed.
 * The upper bound is counted in bytes, and is the one the password hashers of Symfony refuse to go past
 * (CVE-2013-1443): a hash is not asked to swallow a megabyte because someone pasted one.
 *
 * Between the two, the password has to be hard enough to guess (PasswordStrength). The two rules are not
 * the same rule twice: eight characters is what §5.1.1.2 forbids a verifier to go below, and a password of
 * exactly eight cannot reach the rung below either, so what the length rule really buys is the message
 * somebody who typed four characters reads. Past ten or so characters the estimate is the rule that bites,
 * and it bites on "passwordpassword" and on sixteen letters of one kind, which no length ever catches.
 *
 * The floor is here and not in a configuration, because an option to lower it is the option to do it wrong
 * (decision 0006); a tenant raising it is its own matter.
 *
 * **What a list of compromised passwords says is not here.** A lookup is input and output and a value
 * object does none: that rule is BreachedPasswordRefusal, which every use case that writes a password
 * runs before it writes one.
 */
final readonly class PlainPassword
{
    public const int MINIMUM_LENGTH = 8;

    public const int MAXIMUM_BYTES = 4096;

    /**
     * The rung the estimate has to reach: the lowest one it names, sixty bits of search space, which is
     * ten to twelve characters drawing on more than one alphabet.
     *
     * The lowest, because a floor above it stops being an estimate and becomes a length rule in other
     * clothes: eighty bits refuses eleven characters drawing on every alphabet there is, which is a rule
     * about length that does not say so, and §5.1.1.2 asks a verifier not to impose rules of that kind.
     * What catches a password that is long, varied and still known to everybody is not a higher floor, it
     * is the list (BreachedPasswordRefusal).
     */
    public const int MINIMUM_STRENGTH = PasswordStrength::WEAK;

    /**
     * @throws PasswordRefusedException when it is too short, too long, or too easy to guess
     */
    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {
        if (mb_strlen($value) < self::MINIMUM_LENGTH) {
            throw new PasswordRefusedException(
                PasswordRefusal::TooShort,
                sprintf('A password is at least %d characters long.', self::MINIMUM_LENGTH),
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
