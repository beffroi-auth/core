<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;

/**
 * One algorithm a password hash may have been made with, under the name the hash carries (decision 0002):
 * the one this provider produces today, and the ones an import brings from wherever the users came from.
 *
 * The set is not one a specification closes: it is what the deployment is wired with, so it is an
 * interface and a class per algorithm, never an enumeration whose cases would have to be edited to
 * receive an import. What hashes and verifies is another matter and another interface
 * (AlgorithmPasswordHasherInterface): an algorithm the provider can name but not verify is an import it
 * can read back and refuse to use, which is the honest answer, and not a row it cannot load.
 */
interface PasswordAlgorithmInterface extends NamedAlgorithmInterface
{
}
