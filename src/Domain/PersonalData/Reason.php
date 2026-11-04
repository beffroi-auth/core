<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

/**
 * The sentences that say why a readable field gives nothing away, for the fields that share one.
 */
final readonly class Reason
{
    public const string HASHED = 'a digest does not open: the column holds the fingerprint of what was presented and never the value';

    public const string DRAWN_AT_RANDOM = 'drawn at random, and designates nobody without the table that joins it';

    public const string PUBLIC_BY_CONSTRUCTION = 'public by construction: what the browser, the authenticator or the client publishes already';

    public const string NAMES_NO_PERSON = 'names no person: a date, a state, or a count of what the provider did';

    public const string THE_TENANT = 'designates an organisation and not a person';

    public const string THE_SUBJECT = 'a stable pseudonym every token carries in the clear and every table joins on';

    public const string A_CLIENT = 'names a client application, and never a person';

    public const string WHAT_THE_REQUEST_ASKED = 'what a request asked of the protocol, not what the provider knows of the person';

    public const string WHAT_THE_SESSION_PROVED = 'what the session proved, which every token issued from it carries in the clear (OIDC Core §2)';

    public const string AN_ENVELOPE = 'the envelope the KMS of the tenant sealed, and nothing else';

    public const string A_KEY_OF_THE_SUBSCRIPTION = 'a key of the subscription as the browser wrote it (RFC 8291 §3.1), which reaches no browser without the sealed endpoint it was drawn for';

    public const string WHAT_A_TOKEN_IS_BOUND_TO = 'what a token is bound to, the thumbprint of a key or of a certificate (RFC 9449 §6, RFC 8705 §3)';

    public const string DRAWN_BY_THE_CLIENT = 'an opaque value the client drew to tie its own request to the answer it gets back';

    public const string THE_LANGUAGE_ASKED_FOR = 'the language a request asked the page to be served in, not a language the provider knows of the person';
}
