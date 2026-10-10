<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use function ceil;
use InvalidArgumentException;
use function max;
use function sprintf;

/**
 * The page of a list that was asked for (architecture §5.2): which one, and how many rows it holds.
 *
 * A table of the account is read by a person who goes back and forth over a handful of pages of their own
 * rows, so a page here is a number and the rows it skips. What walks a whole journal once reads past the
 * last row it read instead (ActsOfAPersonInterface::everyActOf): the two are not the same question, and
 * neither one's page is the other's.
 */
final readonly class Pagination
{
    /**
     * @throws InvalidArgumentException when the number is not a page, or the page holds no row
     */
    public function __construct(
        public int $number,
        public int $size,
    ) {
        if ($number < 1) {
            throw new InvalidArgumentException(sprintf('A page is numbered from 1, "%d" given.', $number));
        }
        if ($size < 1) {
            throw new InvalidArgumentException(sprintf('A page holds one row or more, "%d" given.', $size));
        }
    }

    /**
     * How many rows of the list come before this page.
     */
    public function offset(): int
    {
        return ($this->number - 1) * $this->size;
    }

    /**
     * This page, held inside a list of that many rows: a page past the last one is the last one, so a link
     * somebody kept or a number they typed reads rows, and never an empty table under a count saying there
     * are some. A list that holds nothing is one page, which is the page that says it holds nothing.
     */
    public function within(int $total): self
    {
        $pages = max(1, (int) ceil($total / $this->size));

        return $this->number > $pages ? new self($pages, $this->size) : $this;
    }
}
