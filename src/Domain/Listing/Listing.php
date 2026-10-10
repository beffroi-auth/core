<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use ArrayIterator;
use function array_slice;
use function ceil;
use function count;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use function max;
use function min;
use function sprintf;
use Traversable;

/**
 * One page of a list, with how long the whole list is (architecture §5.2): what a table of the account
 * shows, and what the control under it needs in order to say where the reader is and where they can go.
 *
 * It is the rows it holds: whoever walks it, counts it or asks what the whole list is worth does so without
 * knowing which of the three they are doing, so a table that was handed a list reads the same written the
 * same way.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class Listing implements Countable, IteratorAggregate
{
    /**
     * @param list<T> $rows  the rows of this page, in the order the list is read
     * @param int     $total how many rows the whole list holds
     *
     * @throws InvalidArgumentException when the whole list is said to be shorter than this page of it
     */
    public function __construct(
        public array $rows,
        public Pagination $pagination,
        public int $total,
    ) {
        if ($total < count($rows)) {
            throw new InvalidArgumentException(sprintf(
                'A list of %d rows cannot hold a page of %d of them.',
                $total,
                count($rows),
            ));
        }
    }

    /**
     * One page of a list that is already held whole: the page kept inside what the list holds, and the rows
     * of it. What a store that answers from memory gives, and what a caller holding every row already does
     * with them, so that neither slices a list in a way of its own.
     *
     * @template R
     *
     * @param list<R> $all the whole list, in the order it is read
     *
     * @return self<R>
     */
    public static function of(array $all, Pagination $pagination): self
    {
        $page = $pagination->within(count($all));

        return new self(array_slice($all, $page->offset(), $page->size), $page, count($all));
    }

    /**
     * How many pages the whole list is read in; one when it holds nothing.
     */
    public function pages(): int
    {
        return max(1, (int) ceil($this->total / $this->pagination->size));
    }

    public function hasPrevious(): bool
    {
        return $this->pagination->number > 1;
    }

    public function hasNext(): bool
    {
        return $this->pagination->number < $this->pages();
    }

    /**
     * The page before this one, which is this one when there is none before it.
     */
    public function previous(): int
    {
        return max(1, $this->pagination->number - 1);
    }

    /**
     * The page after this one, which is this one when there is none after it.
     */
    public function next(): int
    {
        return min($this->pages(), $this->pagination->number + 1);
    }

    /**
     * Which row of the whole list this page opens on, counted from 1; zero when the page holds none.
     */
    public function firstRow(): int
    {
        return $this->rows === [] ? 0 : $this->pagination->offset() + 1;
    }

    /**
     * Which row of the whole list this page ends on; zero when it holds none.
     */
    public function lastRow(): int
    {
        return $this->rows === [] ? 0 : $this->pagination->offset() + count($this->rows);
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }
}
