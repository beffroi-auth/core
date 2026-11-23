<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Listing\Period;

final readonly class JournalQuestion
{
    /**
     * @param list<string> $acts           event names, empty for every act
     * @param list<string> $unlessTheirOwn event names asked only when somebody else than the person did the act
     * @param bool|null $byAnAdministrator the acts an administrator of the tenant did when true, the others when false
     */
    public function __construct(
        public array $acts = [],
        public ?string $client = null,
        public ?Period $period = null,
        public array $unlessTheirOwn = [],
        public ?bool $byAnAdministrator = null,
    ) {
    }
}
