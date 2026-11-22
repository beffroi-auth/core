<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * Whoever gives back a code or a link is told one flat sentence, never which reason refused it; whoever asks for a
 * confirmation is told why none is sent.
 */
final class AddressConfirmationRefusedException extends RuntimeException implements ExceptionInterface
{
}
