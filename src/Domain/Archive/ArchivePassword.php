<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * The passphrase a person chose to have their archive leave under it (issue #483), in memory only, on its
 * way to the container that wraps the file. It is never a row, never a log and never a session: the
 * request that reads it is the request that uses it.
 *
 * **It is longer than a password of this provider, and the reason is not that it matters more.** A
 * password of an account is guessed against a provider that counts the attempts and shuts the door after
 * five (§3.4); this one is guessed against a file somebody holds, for as long as they hold it, at whatever
 * rate their own hardware allows. And the container fixes its own derivation: the AES entry of a zip
 * derives its key with PBKDF2-HMAC-SHA1 over 1000 iterations, a count the format writes down and no
 * caller chooses, so what stands between that file and whoever took it is the length of what was typed
 * and nothing else.
 *
 * So the floor is refused and not warned about (decision 0006), and the page that asks for one asks for
 * several words rather than a password.
 *
 * The upper bound bounds a field of a request and nothing cryptographic: a derivation swallows any length,
 * and a megabyte pasted into that field is not a passphrase.
 */
final readonly class ArchivePassword
{
    /**
     * Counted in characters, because that is what a person typed.
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
