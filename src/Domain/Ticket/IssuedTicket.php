<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Ticket;

use SensitiveParameter;

/**
 * @template-covariant T of Ticket
 */
final readonly class IssuedTicket
{
    /**
     * @param T      $ticket
     * @param string $value  what the link carries, of which the ticket keeps the hash only
     */
    public function __construct(
        public Ticket $ticket,
        #[SensitiveParameter]
        public string $value,
    ) {
    }
}
