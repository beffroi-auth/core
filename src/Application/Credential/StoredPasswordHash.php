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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
