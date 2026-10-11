<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\PasswordAlgorithmsInterface;
use function is_array;
use function is_int;
use function is_string;

/**
 * The document a hash is kept as: the algorithm it was made with, by the name it carries (decision 0002),
 * the encoded form, and the parameters an import keeps apart from the digest.
 *
 * **It is the one place that document is written**, because three things keep a hash and all three keep it
 * this way: the password of a credential, each backup code of a TOTP, and the secret of a client that
 * presents it. Two places that must agree are one place.
 *
 * The algorithm is read back as the class the wiring holds for that name, which is why this is a use case
 * and not a method of the hash: a name nothing wired accounts for is a row this deployment cannot read,
 * and it says so where the document is read rather than handing back an algorithm nobody chose.
 */
final readonly class StoredPasswordHash
{
    private const string ALGORITHM = 'algorithm';

    private const string ENCODED = 'encoded';

    private const string PARAMETERS = 'parameters';

    public function __construct(
        private PasswordAlgorithmsInterface $algorithms,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function document(PasswordHash $hash): array
    {
        return [
            self::ALGORITHM => $hash->algorithm->name(),
            self::ENCODED => $hash->encoded,
            self::PARAMETERS => $hash->parameters,
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document names no algorithm or carries no digest, when
     *                                 the parameters are not named strings or numbers, or when nothing
     *                                 wired carries the algorithm it names
     */
    public function of(array $document): PasswordHash
    {
        $members = new Document($document);
        $algorithm = $members->nullableString(self::ALGORITHM);
        $encoded = $members->nullableString(self::ENCODED);
        if ($algorithm === null || $encoded === null) {
            throw new InvalidArgumentException('A password hash names its algorithm and carries its encoded form.');
        }

        return new PasswordHash($this->algorithm($algorithm), $encoded, $this->parameters($document));
    }

    /**
     * @throws InvalidArgumentException when nothing wired carries that name
     */
    private function algorithm(string $name): PasswordAlgorithmInterface
    {
        try {
            return $this->algorithms->named($name);
        } catch (UnsupportedAlgorithmException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @return array<string, int|string>
     */
    private function parameters(array $document): array
    {
        $parameters = $document[self::PARAMETERS] ?? [];
        if (! is_array($parameters)) {
            throw new InvalidArgumentException('The parameters of a password hash are an object.');
        }
        $kept = [];
        foreach ($parameters as $name => $parameter) {
            if (! is_string($name) || ! is_int($parameter) && ! is_string($parameter)) {
                throw new InvalidArgumentException('The parameters of a password hash map a name to a number or a string.');
            }
            $kept[$name] = $parameter;
        }

        return $kept;
    }
}
