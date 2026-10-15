<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use function array_map;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\RotationSchedule;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use function implode;
use Beffroi\Core\Port\KeyAlgorithmsInterface;
use Beffroi\Core\Port\RotationSchedulesInterface;
use function sprintf;

/**
 * Every algorithm a key pair may be generated for, the registries of one use read as one: what the key
 * commands resolve a "--algorithm" against and what reads a stored key back. It holds no list of its own,
 * so a wiring is written once and the views of it cannot disagree (issue #98). Each registry is read by
 * what a reader of several needs of it (JoseAlgorithmRegistryInterface): the lookup that answers nothing,
 * and the words of its refusal when nothing is what it answered.
 *
 * Each registry comes with the schedule its keys rotate by (JoseKeyFamily, issue #738), so the same list
 * says which family holds an algorithm and what that family waits for: the staging and the succession of a
 * key read it here, and no list of the families is kept beside this one.
 */
final readonly class JoseKeyAlgorithms implements KeyAlgorithmsInterface, RotationSchedulesInterface
{
    /**
     * @param non-empty-list<JoseKeyFamily> $families the family of each use, the one of the signatures
     *                                                first: the order "all()" lists and the order the
     *                                                refusals are read in
     */
    public function __construct(
        private array $families,
    ) {
    }

    /**
     * @return list<JoseKeyAlgorithmInterface>
     */
    public function all(): array
    {
        $algorithms = [];
        foreach ($this->families as $family) {
            foreach ($family->algorithms->all() as $algorithm) {
                $algorithms[] = $algorithm;
            }
        }

        return $algorithms;
    }

    public function named(string $name): JoseKeyAlgorithmInterface
    {
        $refusals = [];
        foreach ($this->families as $family) {
            $algorithm = $family->algorithms->find($name);
            if ($algorithm !== null) {
                return $algorithm;
            }
            $refusals[] = $family->algorithms->refusalOf($name);
        }

        // what a registry does not find, it words for its own use: a name nothing carries is refused
        // with what each of them had to say about it, and the first refusal is the trail
        throw new UnsupportedAlgorithmException(
            implode(' ', array_map(static fn (UnsupportedAlgorithmException $refusal): string => $refusal->getMessage(), $refusals)),
            0,
            $refusals[0],
        );
    }

    /**
     * The schedule of the family whose registry holds an algorithm of that name.
     */
    public function of(KeyAlgorithmInterface $algorithm): RotationSchedule
    {
        foreach ($this->families as $family) {
            if ($family->algorithms->find($algorithm->name()) !== null) {
                return $family->schedule;
            }
        }

        throw new LogicException(sprintf(
            'No family of keys holds the algorithm "%s": it is wired with no staging delay and no age to rotate by.',
            $algorithm->name(),
        ));
    }
}
