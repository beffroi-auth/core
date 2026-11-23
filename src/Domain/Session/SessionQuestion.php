<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Domain\Listing\Period;
use function in_array;

/**
 * What a list of sessions is narrowed to, a criterion left null admitting every session.
 */
final readonly class SessionQuestion
{
    /**
     * @param string|null $provedWith a method reference the session proved (RFC 8176 §2)
     */
    public function __construct(
        public ?Period $opened = null,
        public ?Period $seen = null,
        public ?string $provedWith = null,
    ) {
    }

    public function admits(AuthenticationSession $session): bool
    {
        return (
            $this->opened?->holds($session->establishedAt) !== false
            && $this->seen?->holds($session->lastSeenAt) !== false
            && ($this->provedWith === null || in_array($this->provedWith, $session->proofs->methodReferences(), true))
        );
    }
}
