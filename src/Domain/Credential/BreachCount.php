<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * Nothing asked is not zero: a deployment that looks nothing up never reads as one that found nothing.
 */
final readonly class BreachCount
{
    private function __construct(
        public ?int $times,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function times(int $times): self
    {
        if ($times < 0) {
            throw new InvalidArgumentException('A list counts a password zero times or more.');
        }

        return new self($times);
    }

    public static function nothingAsked(): self
    {
        return new self(null);
    }

    public function wasAsked(): bool
    {
        return $this->times !== null;
    }

    public function isBreached(): bool
    {
        return $this->times !== null && $this->times > 0;
    }
}
