<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Journal\Checkpoint;
use Beffroi\Core\Domain\Journal\Link;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenJournalException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ChainOfActsInterface;
use Beffroi\Core\Port\JournalInterface;
use Psr\Clock\ClockInterface;

/**
 * The oldest acts go first, in the order of the chain, up to the first one still inside the window, and the cut is
 * signed: an act written late for an early moment is forgotten with the acts around it.
 */
final readonly class JournalCut
{
    public const int FORGOTTEN_AT_ONCE = 1000;

    public function __construct(
        private JournalInterface $journal,
        private ChainOfActsInterface $chain,
        private ChainWalk $walk,
        private JournalCheckpoints $checkpoints,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
        private JournalRetention $retention,
    ) {
    }

    /**
     * Nothing is forgotten from a chain that does not follow, neither the acts to forget nor the ones written since the
     * last signature.
     *
     * @throws BrokenJournalException
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when the signature of the cut cannot be kept
     */
    public function forgetPastTheWindow(Tenant $tenant): int
    {
        return $this->writes->atomically(function () use ($tenant): int {
            $this->chain->lockedHead($tenant);
            $last = $this->checkpoints->last($tenant);
            $keptAfter = $this->walk->start($tenant, $last);
            $keptSince = $this->retention->keptSince();
            $cut = null;
            $forgotten = 0;
            foreach ($this->walk->from($tenant, $keptAfter, $last?->head) as $linked) {
                if ($forgotten === self::FORGOTTEN_AT_ONCE || $linked->act->occurredAt >= $keptSince) {
                    break;
                }
                $cut = new Link($linked->position, $linked->hash);
                ++$forgotten;
            }
            if ($cut === null) {
                return 0;
            }
            $signed = $last->head ?? $keptAfter;
            $head = $this->walk->end($tenant, $cut->position > $signed->position ? $cut : $signed);
            $this->journal->forgetUpTo($tenant, $cut->position);
            $this->checkpoints->sign($tenant, new Checkpoint($tenant->id, $head, $cut, $this->clock->now()));

            return $forgotten;
        });
    }
}
