<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Listing\Period;

final readonly class JournalQuestion
{
    /**
     * @param list<string> $acts event names, empty for every act
     */
    public function __construct(
        public array $acts = [],
        public ?string $client = null,
        public ?Period $period = null,
    ) {
    }
}
