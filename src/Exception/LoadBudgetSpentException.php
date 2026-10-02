<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;
use function sprintf;

/**
 * A budget that bounds a load is spent, and the request that would have consumed one more of it is not
 * served (decision 0014). Not a wrong input and nothing about the caller: the same request is served
 * once the window has passed, which is the only thing there is to do and the only thing the refusal
 * says. What bounds a guess refuses otherwise, on the subject of the guess and in the words of the
 * authentication that counted it.
 */
final class LoadBudgetSpentException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param string $budget the budget that refused, which names itself
     * @param int $retryAfter seconds before the window has room again, never zero (RFC 9110 §10.2.3)
     * @param string|null $client the authenticated client the budget was counted on, for the audit
     *                            trail; null for a budget counted on the origin of the request, which
     *                            the context of the refusal carries already
     */
    public function __construct(
        public readonly string $budget,
        public readonly int $retryAfter,
        public readonly ?string $client = null,
    ) {
        parent::__construct(sprintf('The "%s" budget is spent; the request is served again in %d seconds.', $budget, $retryAfter));
    }
}
