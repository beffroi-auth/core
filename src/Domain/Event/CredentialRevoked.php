<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class CredentialRevoked implements ConcernsAPersonInterface, NamesItsAuthorInterface
{
    public const string NAME = 'credential.revoked';

    public Tenant $tenant;

    /**
     * @param Subject|null $administrator the administrator of the tenant who revoked it, by the subject of their account
     */
    public function __construct(
        public Credential&RevocableCredentialInterface $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public ?Subject $administrator = null,
    ) {
        $this->tenant = $credential->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->credential->user->subject;
    }

    public function author(): ?Subject
    {
        return $this->administrator;
    }
}
