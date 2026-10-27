<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

/**
 * The person a session names, by the subject their account carries, read where the store keeps it in the clear
 * (decision 0011): neither the session nor the person is opened, so the answer asks nothing of the KMS. A companion
 * of AuthenticationSessionRepositoryInterface for what announces an act it has done already, a replayed code or a
 * reused refresh token revoked whatever the KMS answers (RFC 6749 §4.1.2, RFC 9700 §4.13.2), or a token a client
 * gave back (RFC 7009 §2.1), whose announcement then stands whatever it answers too (architecture §4.5, §4.12).
 */
interface SubjectsOfSessionsInterface
{
    /**
     * @throws RuntimeException when no session carries that identifier
     */
    public function of(Uuid $session): Subject;
}
