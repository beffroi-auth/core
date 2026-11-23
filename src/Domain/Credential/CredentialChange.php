<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * NIST SP 800-63B-4 §4.1.2.1, §4.5, §4.6
 *
 * Carries no identifier of what changed and no link: the person reads the change on their account, signed in.
 */
final readonly class CredentialChange
{
    public Tenant $tenant;

    public Uuid $userId;

    public Subject $subject;

    /**
     * @param string      $act  the name the journal writes the act down under
     * @param string|null $kind the kind of the credential it changed, null when it changed none of them
     */
    public function __construct(
        User $user,
        public string $act,
        public DateTimeImmutable $occurredAt,
        public ?string $kind = null,
    ) {
        $this->tenant = $user->tenant;
        $this->userId = $user->id;
        $this->subject = $user->subject;
    }
}
