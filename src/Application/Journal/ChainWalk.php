<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Journal\Checkpoint;
use Beffroi\Core\Domain\Journal\Link;
use Beffroi\Core\Domain\Journal\LinkedAct;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenJournalException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\ChainOfActsInterface;
use function count;
use Generator;
use function sprintf;

/**
 * Each link is computed again from the one before it and the act as the database holds it, never read on trust.
 */
final readonly class ChainWalk
{
    public const int AT_ONCE = 500;

    public function __construct(
        private ChainOfActsInterface $chain,
    ) {
    }

    /**
     * The last link the retention forgot, as signed; with no signature yet, the one the first act kept names before it.
     *
     * @throws BrokenJournalException
     */
    public function start(Tenant $tenant, ?Checkpoint $signed): Link
    {
        if ($signed !== null) {
            return $signed->keptAfter;
        }

        $first = $this->chain->linkedAfter($tenant, 0, 1)[0] ?? null;
        if ($first === null) {
            return Link::beforeTheFirst();
        }
        try {
            return $first->before();
        } catch (InvalidArgumentException $exception) {
            throw new BrokenJournalException(
                $first->position,
                sprintf('The act at position %d of the journal names no link before it.', $first->position),
                $exception,
            );
        }
    }

    /**
     * @param Link|null $signed a link the chain passes through when it reaches its position
     *
     * @throws BrokenJournalException at the first act that does not follow the one before it
     *
     * @return Generator<int, LinkedAct> each act, in the order of the chain
     */
    public function from(Tenant $tenant, Link $start, ?Link $signed = null): Generator
    {
        if ($signed !== null && $signed->position === $start->position && ! $signed->equals($start)) {
            throw new BrokenJournalException(
                $start->position,
                sprintf('The journal starts after position %d with another link than the one signed there.', $start->position),
            );
        }
        $last = $start;
        do {
            $page = $this->chain->linkedAfter($tenant, $last->position, self::AT_ONCE);
            foreach ($page as $linked) {
                $position = $last->position + 1;
                if ($linked->position !== $position) {
                    throw new BrokenJournalException($position, sprintf('The act at position %d of the journal is missing.', $position));
                }
                $computed = $last->then($linked->act);
                if (! $linked->follows($last, $computed)) {
                    throw new BrokenJournalException($position, sprintf('The act at position %d of the journal is not the one written there.', $position));
                }
                if ($signed !== null && $signed->position === $position && ! $signed->equals($computed)) {
                    throw new BrokenJournalException($position, sprintf('The acts up to position %d of the journal are not the ones signed.', $position));
                }
                $last = $computed;

                yield $linked;
            }
        } while (count($page) === self::AT_ONCE);
    }

    /**
     * @throws BrokenJournalException
     */
    public function end(Tenant $tenant, Link $start): Link
    {
        $end = $start;
        foreach ($this->from($tenant, $start) as $linked) {
            $end = new Link($linked->position, $linked->hash);
        }

        return $end;
    }
}
