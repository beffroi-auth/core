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

    /**
     * What was proven, or what was presented to prove it, is under the floor the tenant names for every
     * login of its users (issue #327): a password posed to a tenant that signs its users in by passkey, or
     * proofs that do not satisfy the predicate it requires.
     */
    case BelowTheFloor = 'below_the_floor';

    /**
     * A local credential, a password, a passkey or a code, presented for a person of an e-mail domain the tenant
     * federates to an upstream provider, through which alone that person signs in (decision 0029).
     */
    case FederatedDomain = 'federated_domain';

    /**
     * A response of an upstream identity provider the service provider of the tenant does not take (beffroi/saml
     * S-53 to S-55): no request of the browser it answers, a signature, an audience or a recipient refused, a
     * replay, an assertion in clear from a provider asked to encrypt, no subject, no address.
     */
    case UpstreamResponseRefused = 'upstream_response_refused';

    /**
     * The person an upstream identity provider named is one this provider does not create or write (beffroi/saml
     * S-56, decision 0029), the reason of which the refusal of the first sign-in keeps to itself.
     */
    case UpstreamPersonRefused = 'upstream_person_refused';
}
