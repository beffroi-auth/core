<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use ArrayIterator;
use function array_slice;
use function array_values;
use function ceil;
use function count;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use function max;
use function min;
use function range;
use function sort;
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
     * How many pages a control offers whole, rather than the two at each end and the one being read.
     */
    private const int OFFERED_WHOLE = 6;

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

    /**
     * The pages a control under the table offers, in the order it offers them, with null where it skips
     * some: the first two, the page being read with the one before and the one after it, and the last two.
     * A list of six pages or fewer offers all of them, a control that skips nothing needing no gap.
     *
     * Two at each end rather than one, because the first and the last page of a table are where a reader
     * goes back to, and a number beside them says how far the one they are on is from either.
     *
     * @return list<int|null>
     */
    public function numbers(): array
    {
        $pages = $this->pages();
        if ($pages <= self::OFFERED_WHOLE) {
            return array_values(range(1, $pages));
        }

        $offered = [1, 2, $pages - 1, $pages];
        foreach ([$this->pagination->number - 1, $this->pagination->number, $this->pagination->number + 1] as $number) {
            if ($number >= 3 && $number <= ($pages - 2)) {
                $offered[] = $number;
            }
        }
        sort($offered);

        $numbers = [];
        $before = null;
        foreach ($offered as $number) {
            if ($before !== null && ($number - $before) > 1) {
                $numbers[] = null;
            }
            $numbers[] = $number;
            $before = $number;
        }

        return $numbers;
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
