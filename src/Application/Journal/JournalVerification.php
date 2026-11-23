<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Journal\Link;
use Beffroi\Core\Domain\Journal\VerifiedJournal;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenJournalException;
use Beffroi\Core\Port\ChainOfActsInterface;
use function sprintf;

/**
 * The whole chain, from the last act the retention forgot to its end, against the last signature.
 */
final readonly class JournalVerification
{
    public function __construct(
        private ChainOfActsInterface $chain,
        private ChainWalk $walk,
        private JournalCheckpoints $checkpoints,
    ) {
    }

    /**
     * @throws BrokenJournalException at the first act that does not follow, or when the signature does not hold
     */
    public function verify(Tenant $tenant): VerifiedJournal
    {
        $head = $this->chain->head($tenant);
        $signed = $this->checkpoints->last($tenant);
        $start = $this->walk->start($tenant, $signed);
        $end = $start;
        foreach ($this->walk->from($tenant, $start, $signed?->head) as $linked) {
            $end = new Link($linked->position, $linked->hash);
            if ($end->position === $head->position && ! $end->equals($head)) {
                throw new BrokenJournalException(
                    $end->position,
                    sprintf('The act at position %d of the journal is not the head the journal names.', $end->position),
                );
            }
        }
        $reached = $signed === null || $end->position >= $signed->head->position ? $head : $signed->head;
        if ($end->position < $reached->position) {
            throw new BrokenJournalException($end->position + 1, sprintf(
                'The acts from position %d to position %d of the journal are missing.',
                $end->position + 1,
                $reached->position,
            ));
        }
        $outside = $this->chain->actsOutsideTheChain($tenant, $start->position);
        if ($outside > 0) {
            throw new BrokenJournalException(null, sprintf('%d act(s) of the journal are not in its chain.', $outside));
        }

        return new VerifiedJournal($start, $end, $signed);
    }
}
