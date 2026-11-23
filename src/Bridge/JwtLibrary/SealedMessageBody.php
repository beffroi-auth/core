<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Tenant\Tenant;
use function is_string;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Throwable;

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

    /**
     * RFC 7516 §4.1.6
     *
     * Read from the protected header without opening anything: what is still sealed to a key is known without the KMS.
     *
     * @return string|null null for a body that is not a compact JWE sealed for the tenant, or names no key
     */
    public static function keyOf(Tenant $tenant, string $sealed): ?string
    {
        try {
            $header = new CompactSerializer()
                ->unserialize($sealed)
                ->getSharedProtectedHeader();
        } catch (Throwable) {
            return null;
        }
        if (($header[self::TENANT_HEADER] ?? null) !== $tenant->slug) {
            return null;
        }
        $kid = $header['kid'] ?? null;

        return is_string($kid) && $kid !== '' ? $kid : null;
    }
}
