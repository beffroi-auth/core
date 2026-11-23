<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Chain\RelinkedChain;
use Beffroi\Core\Domain\Event\ChainSignedAnew;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ChainOfRowsInterface;
use Beffroi\Core\Port\RelinkableChainsInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Decision 0060: what a person who read the finding of a chain asks for, and nothing runs on its own. The rows are
 * taken as the database holds them, linked again and signed, and the act says who asked.
 */
final readonly class ChainRelinking
{
    public function __construct(
        private ChainOfRowsInterface $rows,
        private RelinkableChainsInterface $relinkable,
        private ChainCheckpoints $checkpoints,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when the links cannot be rewritten, or the signature cannot be kept
     */
    public function signAnew(Tenant $tenant, ChainInterface $chain, RequestContext $context): RelinkedChain
    {
        $relinked = $this->writes->atomically(function () use ($tenant, $chain): RelinkedChain {
            $this->rows->lockedHead($tenant, $chain);
            $relinked = $this->relinkable->relink($tenant, $chain);
            $now = $this->clock->now();
            $this->checkpoints->sign(
                $tenant,
                $chain,
                new Checkpoint($tenant->id, $chain->name(), $relinked->head, $relinked->start, $relinked->head, $now, $now),
            );

            return $relinked;
        });
        $this->events->dispatch(
            new ChainSignedAnew($tenant, $chain->name(), $relinked->start->position + 1, $relinked->head->position, $this->clock->now(), $context),
        );

        return $relinked;
    }
}
