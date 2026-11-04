<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use function mb_strlen;
use SensitiveParameter;

/**
 * NIST SP 800-63B §3.1.1.2
 */
final readonly class PasswordLength
{
    public const int ALONE = 15;

    public const int WITH_A_SECOND_FACTOR = 8;

    private function __construct(
        public int $minimum,
    ) {
    }

    public static function under(AuthenticationPredicateInterface $floor): self
    {
        return new self(
            $floor->isSatisfiedBy([PasswordCredential::method()]) ? self::ALONE : self::WITH_A_SECOND_FACTOR,
        );
    }

    public function reachedBy(#[SensitiveParameter] string $value): bool
    {
        return mb_strlen($value) >= $this->minimum;
    }
}
