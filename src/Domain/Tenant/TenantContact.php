<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_check_encoding;
use function mb_strlen;
use function preg_match;
use function sprintf;

/**
 * NIST SP 800-63B-4 §4.6
 *
 * Written into a message as the tenant wrote it, so a character that breaks the line, hides or turns the text around is
 * refused rather than shown.
 */
final readonly class TenantContact
{
    public const int MAX_LENGTH = 255;

    /**
     * @throws InvalidArgumentException when it is empty, not UTF-8, longer than 255 characters, more than one line, or
     *                                  begins or ends with a space
     */
    public function __construct(
        public string $value,
    ) {
        if (! mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException('The contact of a tenant is not UTF-8.');
        }
        if ($value === '') {
            throw new InvalidArgumentException('The contact of a tenant must not be empty.');
        }
        if (preg_match('/^[\s\p{Z}]|[\s\p{Z}]$/u', $value) === 1) {
            throw new InvalidArgumentException('The contact of a tenant must neither begin nor end with a space.');
        }
        if (preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $value) === 1) {
            throw new InvalidArgumentException('The contact of a tenant is one line of text, without a control, a format or a separator character.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The contact of a tenant must not be longer than %d characters.', self::MAX_LENGTH));
        }
    }

    public function equals(?self $other): bool
    {
        return $other !== null && $this->value === $other->value;
    }
}
