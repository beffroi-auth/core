<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Random;

use function array_shift;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\RandomnessInterface;
use function pack;
use function sprintf;
use function str_pad;
use const STR_PAD_LEFT;
use function strlen;
use function substr;

/**
 * For tests and for the examples: the bytes they wrote, in the order they wrote them, then a sequence
 * that repeats nothing. A test that cares what a value will be says so and reads it back; one that does
 * not still gets a different draw at every call.
 */
final class FixedRandomness implements RandomnessInterface
{
    private int $drawn = 0;

    /**
     * @param list<string> $bytes handed back in order, each one of the length it is asked for
     */
    public function __construct(
        private array $bytes = [],
    ) {
    }

    public function bytes(int $length): string
    {
        ++$this->drawn;
        $next = array_shift($this->bytes);
        if ($next === null) {
            return substr(str_pad(pack('N', $this->drawn), $length, "\x00", STR_PAD_LEFT), -$length);
        }
        if (strlen($next) !== $length) {
            throw new InvalidArgumentException(sprintf('Draw %d was written as %d bytes and is asked for %d.', $this->drawn, strlen($next), $length));
        }

        return $next;
    }
}
