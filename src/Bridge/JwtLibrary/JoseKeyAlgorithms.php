<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use function array_map;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use function implode;
use Beffroi\Core\Port\KeyAlgorithmsInterface;

/**
 * Every algorithm a key pair may be generated for, the registries of one use read as one: what the key
 * commands resolve a "--algorithm" against and what reads a stored key back. It holds no list of its own,
 * so a wiring is written once and the views of it cannot disagree (issue #98). Each registry is read by
 * what a reader of several needs of it (JoseAlgorithmRegistryInterface): the lookup that answers nothing,
 * and the words of its refusal when nothing is what it answered.
 */
final readonly class JoseKeyAlgorithms implements KeyAlgorithmsInterface
{
    /**
     * @param non-empty-list<JoseAlgorithmRegistryInterface> $registries the registry of each use, the one
     *                                                                   of the signatures first: the order
     *                                                                   "all()" lists and the order the
     *                                                                   refusals are read in
     */
    public function __construct(
        private array $registries,
    ) {
    }

    /**
     * @return list<JoseKeyAlgorithmInterface>
     */
    public function all(): array
    {
        $algorithms = [];
        foreach ($this->registries as $registry) {
            foreach ($registry->all() as $algorithm) {
                $algorithms[] = $algorithm;
            }
        }

        return $algorithms;
    }

    public function named(string $name): JoseKeyAlgorithmInterface
    {
        $refusals = [];
        foreach ($this->registries as $registry) {
            $algorithm = $registry->find($name);
            if ($algorithm !== null) {
                return $algorithm;
            }
            $refusals[] = $registry->refusalOf($name);
        }

        // what a registry does not find, it words for its own use: a name nothing carries is refused
        // with what each of them had to say about it, and the first refusal is the trail
        throw new UnsupportedAlgorithmException(
            implode(' ', array_map(static fn (UnsupportedAlgorithmException $refusal): string => $refusal->getMessage(), $refusals)),
            0,
            $refusals[0],
        );
    }
}
