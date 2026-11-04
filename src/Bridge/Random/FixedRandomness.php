<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Random;

use function array_shift;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\RandomnessInterface;
use function pack;
use function sprintf;
use function str_pad;
use const STR_PAD_LEFT;
use function strlen;
use function substr;

final class FixedRandomness implements RandomnessInterface
{
    private int $drawn = 0;

    /**
     * @param list<string> $bytes handed back in order, each of the length asked for
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
