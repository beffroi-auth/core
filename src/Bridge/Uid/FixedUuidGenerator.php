<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Uid;

use function array_shift;
use Beffroi\Core\Port\UuidGeneratorInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

final class FixedUuidGenerator implements UuidGeneratorInterface
{
    private int $drawn = 0;

    /**
     * @param list<Uuid> $uuids handed back in order, whichever of the two methods asks
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
