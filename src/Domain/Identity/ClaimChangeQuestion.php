<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Listing\Period;

/**
 * What a history of changes is narrowed to, a criterion left null admitting every change.
 */
final readonly class ClaimChangeQuestion
{
    /**
     * @param string|null $claim the name ClaimChange::$claim holds
     * @param bool|null $byAnAdministrator the changes an administrator of the tenant made when true, those the person made when false
     */
    public function __construct(
        public ?Period $period = null,
        public ?string $claim = null,
        public ?bool $byAnAdministrator = null,
    ) {
    }

    public function admits(ClaimChange $change): bool
    {
        return (
            $this->period?->holds($change->changedAt) !== false
            && ($this->claim === null || $change->claim === $this->claim)
            && ($this->byAnAdministrator === null || ($change->author !== null) === $this->byAnAdministrator)
        );
    }
}
