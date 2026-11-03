<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A list of models of authenticator is not one the provider stores (architecture §3.4): not a JSON object, a
 * member that is not an AAGUID, a model without a name, a logo that is not an image of a data: URL, or an object
 * that names nothing, which is how the list of the community says it was retired. Nothing of it is kept, and the
 * list stored before stays.
 */
final class AuthenticatorListRefusedException extends RuntimeException implements ExceptionInterface
{
}
