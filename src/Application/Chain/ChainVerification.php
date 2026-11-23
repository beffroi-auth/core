<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Chain\Link;
use Beffroi\Core\Domain\Chain\VerifiedChain;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenChainException;
use Beffroi\Core\Port\ChainOfRowsInterface;
use const DATE_ATOM;
use function sprintf;

final readonly class ChainVerification
{
    public function __construct(
        private ChainOfRowsInterface $rows,
        private ChainWalk $walk,
        private ChainCheckpoints $checkpoints,
    ) {
    }

    /**
     * Every row kept, from the last one the retention forgot to the end, against the last signature.
     *
     * @throws BrokenChainException at the first row that does not follow, or when the signature does not hold
     */
    public function whole(Tenant $tenant, ChainInterface $chain): VerifiedChain
    {
        $head = $this->rows->head($tenant, $chain);
        $signed = $this->checkpoints->last($tenant, $chain);
        $start = $this->walk->start($tenant, $chain, $signed);
        $end = $start;
        $firstMoment = null;
        foreach ($this->walk->from($tenant, $chain, $start, $signed?->head) as $linked) {
            $firstMoment ??= $linked->row->moment;
            $end = new Link($linked->position, $linked->hash);
            if ($end->position === $head->position && ! $end->equals($head)) {
                throw new BrokenChainException(
                    $chain->name(),
                    $end->position,
                    sprintf('The row at position %d of the chain "%s" is not the head the chain names.', $end->position, $chain->name()),
                );
            }
        }
        self::reaches($chain, $end, $signed, $head);
        $outside = $this->rows->rowsOutsideTheChain($tenant, $chain, $start->position);
        if ($outside > 0) {
            throw new BrokenChainException($chain->name(), null, sprintf('%d row(s) of the chain "%s" are not in it.', $outside, $chain->name()));
        }

        return new VerifiedChain($chain->name(), $start, $end, $signed, $firstMoment);
    }

    /**
     * The whole chain, and a signed head kept outside the instance, which it must still hold unless the retention forgot
     * past it: a chain cut short with its signature put back is seen there.
     *
     * @throws BrokenChainException
     */
    public function wholeAgainst(Tenant $tenant, ChainInterface $chain, string $copy): VerifiedChain
    {
        $kept = $this->checkpoints->read($tenant, $chain, $copy);
        $verified = $this->whole($tenant, $chain);
        $position = $kept->head->position;
        if ($position > $verified->end->position) {
            throw new BrokenChainException($chain->name(), $verified->end->position + 1, sprintf(
                'The chain "%s" ends at position %d, and a copy of its head signed at %s holds position %d.',
                $chain->name(),
                $verified->end->position,
                $kept->signedAt->format(DATE_ATOM),
                $position,
            ));
        }
        if ($position > $verified->start->position) {
            $row = $this->rows->linkedAfter($tenant, $chain, $position - 1, 1)[0] ?? null;
            if ($row === null || $row->position !== $position || $row->hash !== $kept->head->hash) {
                throw new BrokenChainException($chain->name(), $position, sprintf(
                    'The rows up to position %d of the chain "%s" are not the ones a copy of its head signed at %s holds.',
                    $position,
                    $chain->name(),
                    $kept->signedAt->format(DATE_ATOM),
                ));
            }
        }

        return $verified;
    }

    /**
     * The signature, the row it signs, the first row kept and the rows written since, in a time that does not grow
     * with the chain; a chain signed by nobody yet is walked whole.
     *
     * @throws BrokenChainException
     */
    public function sinceTheSignature(Tenant $tenant, ChainInterface $chain): VerifiedChain
    {
        $signed = $this->checkpoints->last($tenant, $chain);
        if ($signed === null) {
            return $this->whole($tenant, $chain);
        }
        if ($signed->broken !== null) {
            throw new BrokenChainException($chain->name(), $signed->broken, sprintf(
                'The walk of the whole chain "%s" at %s found the row at position %d not following the one before it.',
                $chain->name(),
                $signed->walkedAt->format(DATE_ATOM),
                $signed->broken,
            ));
        }
        $head = $this->rows->head($tenant, $chain);
        $first = $this->rows->linkedAfter($tenant, $chain, 0, 1)[0] ?? null;
        $expected = $signed->keptAfter->position + 1;
        if ($first !== null && $first->position !== $expected) {
            throw new BrokenChainException(
                $chain->name(),
                $expected,
                sprintf('The row at position %d of the chain "%s" is missing.', $expected, $chain->name()),
            );
        }
        if ($signed->head->position > $signed->keptAfter->position) {
            $this->signedRowHolds($tenant, $chain, $signed);
        }
        $end = $signed->head;
        foreach ($this->walk->from($tenant, $chain, $signed->head) as $linked) {
            $end = new Link($linked->position, $linked->hash);
            if ($end->position === $head->position && ! $end->equals($head)) {
                throw new BrokenChainException(
                    $chain->name(),
                    $end->position,
                    sprintf('The row at position %d of the chain "%s" is not the head the chain names.', $end->position, $chain->name()),
                );
            }
        }
        self::reaches($chain, $end, $signed, $head);

        return new VerifiedChain($chain->name(), $signed->keptAfter, $end, $signed, $first?->row->moment);
    }

    /**
     * @throws BrokenChainException
     */
    private function signedRowHolds(Tenant $tenant, ChainInterface $chain, Checkpoint $signed): void
    {
        $position = $signed->head->position;
        $row = $this->rows->linkedAfter($tenant, $chain, $position - 1, 1)[0] ?? null;
        if ($row === null || $row->position !== $position) {
            throw new BrokenChainException(
                $chain->name(),
                $position,
                sprintf('The row at position %d of the chain "%s" is missing.', $position, $chain->name()),
            );
        }
        $before = ChainWalk::before($chain, $row);
        if (! $row->follows($before, $before->then($row->row)) || $row->hash !== $signed->head->hash) {
            throw new BrokenChainException(
                $chain->name(),
                $position,
                sprintf('The rows up to position %d of the chain "%s" are not the ones signed.', $position, $chain->name()),
            );
        }
    }

    /**
     * @throws BrokenChainException
     */
    private static function reaches(ChainInterface $chain, Link $end, ?Checkpoint $signed, Link $head): void
    {
        $reached = $signed === null || $end->position >= $signed->head->position ? $head : $signed->head;
        if ($end->position < $reached->position) {
            throw new BrokenChainException($chain->name(), $end->position + 1, sprintf(
                'The rows from position %d to position %d of the chain "%s" are missing.',
                $end->position + 1,
                $reached->position,
                $chain->name(),
            ));
        }
    }
}
