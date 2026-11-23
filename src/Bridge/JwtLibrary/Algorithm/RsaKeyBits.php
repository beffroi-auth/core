<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Domain\Key\RsaModulus;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;

/**
 * RFC 7518 §3.3, NIST SP 800-57 Part 1 Rev. 5 §5.6
 */
final readonly class RsaKeyBits
{
    public const int MINIMUM = RsaModulus::MINIMUM_BITS;

    public const int DEFAULT = 3072;

    public function __construct(
        public int $value = self::DEFAULT,
    ) {
        if ($value < self::MINIMUM || ($value % 8) !== 0) {
            throw new InvalidArgumentException(sprintf('RSA keys are at least %d bits, in whole bytes.', self::MINIMUM));
        }
    }
}
