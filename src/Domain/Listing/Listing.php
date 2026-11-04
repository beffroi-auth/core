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
 * One page of a list, with how many rows the whole list holds.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class Listing implements Countable, IteratorAggregate
{
    /**
     * Up to this many pages, a control offers every one and skips none.
     */
    private const int OFFERED_WHOLE = 6;

    /**
     * @param list<T> $rows
     *
     * @throws InvalidArgumentException when the whole list is said to be shorter than this page
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
     * @template R
     *
     * @param list<R> $all
     *
     * @return self<R>
     */
    public static function of(array $all, Pagination $pagination): self
    {
        $page = $pagination->within(count($all));

        return new self(array_slice($all, $page->offset(), $page->size), $page, count($all));
    }

    public function pages(): int
    {
        return max(1, (int) ceil($this->total / $this->pagination->size));
    }

    /**
     * @return list<int|null> the pages to offer, null where some are skipped
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

    public function previous(): int
    {
        return max(1, $this->pagination->number - 1);
    }

    public function next(): int
    {
        return min($this->pages(), $this->pagination->number + 1);
    }

    public function firstRow(): int
    {
        return $this->rows === [] ? 0 : $this->pagination->offset() + 1;
    }

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
