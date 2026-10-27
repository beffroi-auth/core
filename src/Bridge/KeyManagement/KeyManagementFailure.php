<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Symfony\Component\KeyManagement\Exception\DataKeyNotFoundException;
use Symfony\Component\KeyManagement\Exception\DecryptionFailedException;
use Symfony\Component\KeyManagement\Exception\RuntimeException as KeyManagementRuntimeException;
use Symfony\Component\KeyManagement\Exception\UnsupportedOperationException;
use Throwable;

/**
 * What a failure of symfony/key-management means for the provider, decided once for both sealers and for the
 * data key store of the bundle. A backend that did not answer or refused (a transport failure, an error of the
 * KMS, a master key the KMS does not hold when it seals) is an outage, KmsUnavailableException: the call is
 * served again once the KMS is back. An envelope the component refuses is a RuntimeException as before, a bug
 * or an attack: invalid, tampered, foreign to this use or this tenant, and what the component deliberately
 * makes indistinguishable from it (a key the KMS does not know when it opens, a version of the master key
 * Vault no longer decrypts). So is what a backend cannot do by configuration, and any failure that is not the
 * component's.
 */
final readonly class KeyManagementFailure
{
    public static function of(Throwable $cause, string $message): RuntimeException|KmsUnavailableException
    {
        return self::outageOf($cause, $message) ?? new RuntimeException($message, 0, $cause);
    }

    /**
     * The outage the failure is, null when it is none: what a caller bound to the contract of the component
     * reports as the outage it is, and leaves as the component threw it otherwise.
     */
    public static function outageOf(Throwable $cause, string $message): ?KmsUnavailableException
    {
        if ($cause instanceof DecryptionFailedException || $cause instanceof DataKeyNotFoundException || $cause instanceof UnsupportedOperationException) {
            return null;
        }

        return $cause instanceof KeyManagementRuntimeException ? new KmsUnavailableException($message, 0, $cause) : null;
    }
}
