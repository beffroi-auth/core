<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;
use function sprintf;

final class LoadBudgetSpentException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param int $retryAfter seconds, never zero
     * @param string|null $client null for a budget counted on the origin of the request
     */
    public function __construct(
        public readonly string $budget,
        public readonly int $retryAfter,
        public readonly ?string $client = null,
    ) {
        parent::__construct(sprintf('The "%s" budget is spent; the request is served again in %d seconds.', $budget, $retryAfter));
    }
}
