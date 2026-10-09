<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

/**
 * What the two halves of a sealed message body agree on (decision 0020): the one member of the protected
 * header that is not of JOSE, the scope the body was sealed under.
 *
 * It is in the protected header because the protected header is the additional authenticated data of a
 * compact JWE (RFC 7516 §5.1): the scope is sealed with the body, so a body replayed under another scope
 * fails to open instead of opening somewhere else. What a transport carries beside the body says the same
 * thing and is not what decides, a header being whatever whoever wrote it wanted (the pattern of
 * beffroi#436).
 */
final readonly class SealedMessageBody
{
    /**
     * The slug of the tenant the body was sealed for. A private header member (RFC 7516 §4.3), named for
     * this provider so that nothing of JOSE collides with it.
     */
    public const string TENANT_HEADER = 'beffroi_tenant';
}
