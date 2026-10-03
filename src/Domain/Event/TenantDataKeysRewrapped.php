<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The data keys of a tenant are wrapped by another master key, or by another KMS, from now on. No sealed
 * value was read and none was rewritten: a data key is what changed protection, and every ciphertext goes
 * on referring to the same key.
 *
 * It carries the two clients by name and never a client object: what names a KMS in this provider is the
 * name the configuration gives it, and that is what survives the queue it is written to.
 */
final readonly class TenantDataKeysRewrapped implements DomainEventInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'tenant.data_keys_rewrapped';

    /**
     * @param int $keys how many wrapped keys moved
     * @param int $scopes over how many scopes, which is how many families of columns the tenant had sealed
     * @param string|null $from the client that wrapped them until now, null when the run moved whatever it
     *                          found, several clients possibly among it
     * @param string $to the client that wraps them from now on
     * @param string $masterKeyId the master key of that client the keys are wrapped by from now on
     */
    public function __construct(
        public Tenant $tenant,
        public int $keys,
        public int $scopes,
        public ?string $from,
        public string $to,
        public string $masterKeyId,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }
}
