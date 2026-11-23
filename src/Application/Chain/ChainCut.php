<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Chain\Link;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenChainException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ChainOfRowsInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * The whole chain is walked against its signature first, outside the lock, so that a row moved back with its links
 * computed again is never cut. Then the oldest rows go, in the order of the chain, up to the first one still inside
 * the window, and the chain is signed again, with the cut and the walk it was given.
 */
final readonly class ChainCut
{
    public const int FORGOTTEN_AT_ONCE = 1000;

    public function __construct(
        private ChainOfRowsInterface $rows,
        private ChainWalk $walk,
        private ChainVerification $verification,
        private ChainCheckpoints $checkpoints,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BrokenChainException
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when the signature cannot be kept
     */
    public function forgetPastTheWindow(Tenant $tenant, ChainInterface $chain, DateTimeImmutable $keptSince): int
    {
        $walkedAt = $this->clock->now();
        try {
            $walked = $this->verification->whole($tenant, $chain)->end;
        } catch (BrokenChainException $broken) {
            $this->signWhatTheWalkFound($tenant, $chain, $broken, $walkedAt);

            throw $broken;
        }

        return $this->writes->atomically(function () use ($tenant, $chain, $keptSince, $walked, $walkedAt): int {
            $this->rows->lockedHead($tenant, $chain);
            $last = $this->checkpoints->last($tenant, $chain);
            $keptAfter = $this->walk->start($tenant, $chain, $last);
            $cut = $keptAfter;
            $forgotten = 0;
            foreach ($this->walk->from($tenant, $chain, $keptAfter, $last?->head) as $linked) {
                if ($forgotten === self::FORGOTTEN_AT_ONCE || $linked->row->moment >= $keptSince) {
                    break;
                }
                $cut = new Link($linked->position, $linked->hash);
                ++$forgotten;
            }
            $signed = $last->head ?? $keptAfter;
            $head = $this->walk->end($tenant, $chain, $cut->position > $signed->position ? $cut : $signed);
            if ($forgotten > 0) {
                $this->rows->forgetUpTo($tenant, $chain, $cut->position);
            }
            $this->checkpoints->sign($tenant, $chain, new Checkpoint(
                $tenant->id,
                $chain->name(),
                $head,
                $cut,
                $walked->position >= $cut->position ? $walked : $cut,
                $walkedAt,
                $this->clock->now(),
            ));

            return $forgotten;
        });
    }

    /**
     * The last signature signed again with the position the walk found broken, so that the walk since the signature
     * says it until the chain holds together again; a signature at fault itself is left as it is.
     *
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException
     */
    private function signWhatTheWalkFound(Tenant $tenant, ChainInterface $chain, BrokenChainException $broken, DateTimeImmutable $walkedAt): void
    {
        if ($broken->position === null) {
            return;
        }
        $this->writes->atomically(function () use ($tenant, $chain, $broken, $walkedAt): void {
            $this->rows->lockedHead($tenant, $chain);
            try {
                $last = $this->checkpoints->last($tenant, $chain);
            } catch (BrokenChainException) {
                return;
            }
            if ($last === null) {
                return;
            }
            $this->checkpoints->sign($tenant, $chain, new Checkpoint(
                $tenant->id,
                $chain->name(),
                $last->head,
                $last->keptAfter,
                $last->walked,
                $walkedAt,
                $this->clock->now(),
                $broken->position,
            ));
        });
    }
}
