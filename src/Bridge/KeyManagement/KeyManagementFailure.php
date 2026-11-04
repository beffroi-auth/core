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
 * A backend that did not answer or refused is an outage. An envelope the component refuses is a bug or an attack,
 * and so is what it deliberately makes indistinguishable from one, or what a backend cannot do by configuration.
 */
final readonly class KeyManagementFailure
{
    public static function of(Throwable $cause, string $message): RuntimeException|KmsUnavailableException
    {
        return self::outageOf($cause, $message) ?? new RuntimeException($message, 0, $cause);
    }

    public static function outageOf(Throwable $cause, string $message): ?KmsUnavailableException
    {
        if ($cause instanceof DecryptionFailedException || $cause instanceof DataKeyNotFoundException || $cause instanceof UnsupportedOperationException) {
            return null;
        }

        return $cause instanceof KeyManagementRuntimeException ? new KmsUnavailableException($message, 0, $cause) : null;
    }
}
