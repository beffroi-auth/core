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

final readonly class JoseKeyAlgorithms implements KeyAlgorithmsInterface, RotationSchedulesInterface
{
    /**
     * @param non-empty-list<JoseKeyFamily> $families one per use, the signatures first
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

        throw new UnsupportedAlgorithmException(
            implode(' ', array_map(static fn (UnsupportedAlgorithmException $refusal): string => $refusal->getMessage(), $refusals)),
            0,
            $refusals[0],
        );
    }

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
