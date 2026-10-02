<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\ApplicationServerAlgorithmsInterface;
use function implode;
use function sprintf;

/**
 * The algorithms of the keys the tenant hands out itself, as the wiring gave them. A deployment that
 * reaches nobody by push wires none, and then there is no such key to draw and no page that hands one
 * out: unlike the signature registry, an empty one is a deployment and not a mistake.
 */
final readonly class JoseApplicationServerAlgorithms implements ApplicationServerAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseApplicationServerAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseApplicationServerAlgorithmInterface> $algorithms in the order they are wired
     */
    public function __construct(iterable $algorithms)
    {
        $this->wired = new WiredAlgorithms($algorithms);
    }

    /**
     * @return list<JoseApplicationServerAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->wired->algorithms;
    }

    public function named(string $name): JoseApplicationServerAlgorithmInterface
    {
        return (
            $this->wired->named($name) ?? throw new UnsupportedAlgorithmException(sprintf(
                'This provider hands out no key named "%s"; it hands out %s.',
                $name,
                $this->wired->algorithms === [] ? 'none' : implode(', ', $this->wired->names()),
            ))
        );
    }
}
