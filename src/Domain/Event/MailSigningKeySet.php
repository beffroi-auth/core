<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * A key the tenant signs its mail with, set by an administrator in place of the one of its format, which signs nothing
 * from then on: an act of the tenant and of no person.
 */
final readonly class MailSigningKeySet implements NamesItsAuthorInterface
{
    public const string NAME = 'tenant.mail_signing_key_set';

    /**
     * @param Subject $administrator the administrator of the tenant who did it, by the subject of their account
     */
    public function __construct(
        public Tenant $tenant,
        public string $kind,
        public string $fingerprint,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public Subject $administrator,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function author(): Subject
    {
        return $this->administrator;
    }
}
