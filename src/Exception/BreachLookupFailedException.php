<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * The list of compromised passwords could not be asked, or answered something that is not a list: a
 * timeout, a status that is not a success, a body that does not read as counted suffixes.
 *
 * **It is an exception and not a count of zero** (decision 0006: no boolean coming from a third party is
 * ever cast). What a caller makes of an unknown is the caller's to say, and each one says it: the console
 * and the page that pose a password refuse it, the lane that looks up a password already posed leaves the
 * credential unmarked and comes back later.
 */
final class BreachLookupFailedException extends RuntimeException implements ExceptionInterface
{
}
