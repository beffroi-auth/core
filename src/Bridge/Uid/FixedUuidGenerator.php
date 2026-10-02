<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Uid;

use function array_shift;
use Beffroi\Core\Port\UuidGeneratorInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * For tests and for the examples: the identifiers they wrote, in the order they wrote them, then a
 * sequence that repeats nothing. A test that cares what a row will be called says so and reads it back;
 * one that does not still gets a different identifier at every call.
 */
final class FixedUuidGenerator implements UuidGeneratorInterface
{
    private int $drawn = 0;

    /**
     * @param list<Uuid> $uuids handed back in order, whichever of the two methods asks for one
     */
    public function __construct(
        private array $uuids = [],
    ) {
    }

    public function timeOrdered(): Uuid
    {
        return $this->next(7);
    }

    public function opaque(): Uuid
    {
        return $this->next(4);
    }

    private function next(int $version): Uuid
    {
        ++$this->drawn;
        $next = array_shift($this->uuids);

        return $next ?? Uuid::fromString(sprintf('00000000-0000-%d000-8000-%012x', $version, $this->drawn));
    }
}
