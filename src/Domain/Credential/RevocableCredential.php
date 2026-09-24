<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Exception\LogicException;
use function sprintf;

/**
 * A credential that can be taken out of use without leaving its row. The revocation is the same fact
 * whatever the kind, and the same rule: the row stays for the audit trail, the first revocation wins, and
 * a revoked credential authenticates nobody again. Written here once, so that a kind carries only what
 * makes it that kind; what a revoked credential refuses to do afterwards is the kind's (recordUse()).
 *
 * A password is not one of these: it is replaced, never revoked.
 */
abstract class RevocableCredential extends Credential implements RevocableCredentialInterface
{
    public private(set) ?DateTimeImmutable $revokedAt = null;

    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * A revoked credential never authenticates again: the rule of every kind that can be revoked, so it
     * is stated once and each kind calls it where it would otherwise restate it.
     *
     * @throws LogicException
     */
    protected function refuseIfRevoked(): void
    {
        if ($this->revokedAt !== null) {
            throw new LogicException(sprintf('A revoked %s never authenticates again.', $this->kind()));
        }
    }
}
