<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
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
 */
final readonly class PlainPassword
{
    public const int MINIMUM_LENGTH = 8;

    public const int MAXIMUM_BYTES = 4096;

    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {
        if (mb_strlen($value) < self::MINIMUM_LENGTH) {
            throw new InvalidArgumentException(sprintf('A password is at least %d characters long.', self::MINIMUM_LENGTH));
        }
        if (strlen($value) > self::MAXIMUM_BYTES) {
            throw new InvalidArgumentException(sprintf('A password is at most %d bytes long.', self::MAXIMUM_BYTES));
        }
    }
}
