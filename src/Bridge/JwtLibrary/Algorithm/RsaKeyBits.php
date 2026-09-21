<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Exception\InvalidArgumentException;
use function sprintf;

/**
 * The size of an RSA modulus, in bits: 2048 at the least (RFC 7518 §3.3), 3072 by default, which is 128
 * bits of security (NIST SP 800-57 Part 1 Rev. 5 §5.6). Shared by the RSA algorithms, so that the rule
 * is written once.
 */
final readonly class RsaKeyBits
{
    public const int MINIMUM = 2048;

    public const int DEFAULT = 3072;

    public function __construct(
        public int $value = self::DEFAULT,
    ) {
        if ($value < self::MINIMUM || $value % 8 !== 0) {
            throw new InvalidArgumentException(sprintf('RSA keys are at least %d bits, in whole bytes.', self::MINIMUM));
        }
    }
}
