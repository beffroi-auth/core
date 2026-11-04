<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * Never a row, a log or a session: the request that reads it is the request that uses it.
 */
final readonly class ArchivePassword
{
    /**
     * Counted in characters. Longer than a password: it is guessed offline against a zip, whose AES entry derives
     * its key with PBKDF2-HMAC-SHA1 over 1000 iterations no caller chooses.
     */
    public const int MINIMUM_LENGTH = 16;

    public const int MAXIMUM_BYTES = 256;

    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {
        if (mb_strlen($value) < self::MINIMUM_LENGTH) {
            throw new InvalidArgumentException(sprintf('A passphrase for an archive is at least %d characters long.', self::MINIMUM_LENGTH));
        }
        if (strlen($value) > self::MAXIMUM_BYTES) {
            throw new InvalidArgumentException(sprintf('A passphrase for an archive is at most %d bytes long.', self::MAXIMUM_BYTES));
        }
    }
}
