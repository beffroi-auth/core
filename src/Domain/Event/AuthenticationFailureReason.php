<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

/**
 * Why an authentication attempt failed, for the record. The user is told nothing more than "failed": the
 * distinction stays in the audit trail.
 */
enum AuthenticationFailureReason: string
{
    case UnknownUser = 'unknown_user';
    case NoSuchCredential = 'no_such_credential';
    case InvalidCredential = 'invalid_credential';
    case DisabledUser = 'disabled_user';
}
