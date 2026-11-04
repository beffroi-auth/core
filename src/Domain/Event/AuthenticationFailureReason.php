<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

/**
 * For the audit trail only; the user is told nothing more than "failed".
 */
enum AuthenticationFailureReason: string
{
    case UnknownUser = 'unknown_user';
    case NoSuchCredential = 'no_such_credential';
    case InvalidCredential = 'invalid_credential';
    case DisabledUser = 'disabled_user';
    case RevokedCredential = 'revoked_credential';
    case ReplayedCode = 'replayed_code';
    case TooManyAttempts = 'too_many_attempts';
    case ExpiredLogin = 'expired_login';

    case BelowTheFloor = 'below_the_floor';

    case FederatedDomain = 'federated_domain';

    case UpstreamResponseRefused = 'upstream_response_refused';

    case UpstreamPersonRefused = 'upstream_person_refused';
}
