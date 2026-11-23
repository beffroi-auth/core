<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Chain\Link;
use Beffroi\Core\Domain\Chain\LinkedRow;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenChainException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\ChainOfRowsInterface;
use function count;
use Generator;
use function sprintf;

/**
 * Each link is computed again from the one before it and the row as the database holds it, never read on trust.
 */
final readonly class ChainWalk
{
    public const int AT_ONCE = 500;

    public function __construct(
        private ChainOfRowsInterface $rows,
    ) {
    }

    /**
     * The last link the retention forgot, as signed; with no signature yet, the one the first row kept names before it.
     *
     * @throws BrokenChainException
     */
    public function start(Tenant $tenant, ChainInterface $chain, ?Checkpoint $signed): Link
    {
        if ($signed !== null) {
            return $signed->keptAfter;
        }
        $first = $this->rows->linkedAfter($tenant, $chain, 0, 1)[0] ?? null;
        if ($first === null) {
            return Link::beforeTheFirst();
        }

        return self::before($chain, $first);
    }

    /**
     * @param Link|null $signed a link the chain passes through when it reaches its position
     *
     * @throws BrokenChainException at the first row that does not follow the one before it
     *
     * @return Generator<int, LinkedRow> each row, in the order of the chain
     */
    public function from(Tenant $tenant, ChainInterface $chain, Link $start, ?Link $signed = null): Generator
    {
        if ($signed !== null && $signed->position === $start->position && ! $signed->equals($start)) {
            throw new BrokenChainException(
                $chain->name(),
                $start->position,
                sprintf('The chain "%s" starts after position %d with another link than the one signed there.', $chain->name(), $start->position),
            );
        }
        $last = $start;
        do {
            $page = $this->rows->linkedAfter($tenant, $chain, $last->position, self::AT_ONCE);
            foreach ($page as $linked) {
                $position = $last->position + 1;
                if ($linked->position !== $position) {
                    throw new BrokenChainException(
                        $chain->name(),
                        $position,
                        sprintf('The row at position %d of the chain "%s" is missing.', $position, $chain->name()),
                    );
                }
                $computed = $last->then($linked->row);
                if (! $linked->follows($last, $computed)) {
                    throw new BrokenChainException(
                        $chain->name(),
                        $position,
                        sprintf('The row at position %d of the chain "%s" is not the one written there.', $position, $chain->name()),
                    );
                }
                if ($signed !== null && $signed->position === $position && ! $signed->equals($computed)) {
                    throw new BrokenChainException(
                        $chain->name(),
                        $position,
                        sprintf('The rows up to position %d of the chain "%s" are not the ones signed.', $position, $chain->name()),
                    );
                }
                $last = $computed;

                yield $linked;
            }
        } while (count($page) === self::AT_ONCE);
    }

    /**
     * @throws BrokenChainException
     */
    public function end(Tenant $tenant, ChainInterface $chain, Link $start): Link
    {
        $end = $start;
        foreach ($this->from($tenant, $chain, $start) as $linked) {
            $end = new Link($linked->position, $linked->hash);
        }

        return $end;
    }

    /**
     * @throws BrokenChainException
     */
    public static function before(ChainInterface $chain, LinkedRow $linked): Link
    {
        try {
            return $linked->before();
        } catch (InvalidArgumentException $exception) {
            throw new BrokenChainException(
                $chain->name(),
                $linked->position,
                sprintf('The row at position %d of the chain "%s" names no link before it.', $linked->position, $chain->name()),
                $exception,
            );
        }
    }
}
