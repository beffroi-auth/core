<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

/**
 * The tenant is in the protected header, the additional authenticated data of a compact JWE (RFC 7516 §5.1),
 * so that a body replayed under another tenant fails to open.
 */
final readonly class SealedMessageBody
{
    /**
     * RFC 7516 §4.3
     */
    public const string TENANT_HEADER = 'beffroi_tenant';
}
