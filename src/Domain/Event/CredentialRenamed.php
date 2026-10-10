<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\NameableCredentialInterface;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * The person who holds the credential gave it another name. What it proves is untouched: a name tells one
 * credential from another on the page where they are listed, and nothing of a ceremony reads it.
 *
 * The name it had is on the event, because the journal of a person is read to recognise what they did, and
 * "the key you called X is called Y" is what they did.
 */
final readonly class CredentialRenamed implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
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
