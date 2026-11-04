<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use function ceil;
use InvalidArgumentException;
use function max;
use function sprintf;

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

    public function offset(): int
    {
        return ($this->number - 1) * $this->size;
    }

    /**
     * A page past the last one of a list of that many rows is the last one.
     */
    public function within(int $total): self
    {
        $pages = max(1, (int) ceil($total / $this->size));

        return $this->number > $pages ? new self($pages, $this->size) : $this;
    }
}
