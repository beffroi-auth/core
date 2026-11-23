<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

/**
 * A refusal the journal writes with its reason and the method it refused, both words this provider gives them and
 * never a value a request carried (decision 0062).
 */
interface SaysWhyItWasRefusedInterface extends DomainEventInterface
{
    /**
     * @return non-empty-string the reason, by the name its enumeration gives it
     */
    public function refusalReason(): string;

    /**
     * @return string|null the method refused, by the name this provider gives it, null when the request named none
     */
    public function refusedMethod(): ?string;
}
