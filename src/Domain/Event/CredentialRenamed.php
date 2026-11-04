<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\NameableCredentialInterface;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

final readonly class CredentialRenamed implements ConcernsAPersonInterface
{
    public const string NAME = 'credential.renamed';

    public Tenant $tenant;

    public function __construct(
        public Credential&NameableCredentialInterface $credential,
        public string $was,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
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
}
