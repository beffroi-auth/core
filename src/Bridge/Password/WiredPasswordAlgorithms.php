<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\PasswordAlgorithmsInterface;
use function implode;
use function sprintf;

/**
 * The password algorithms the wiring gave the provider, held and looked up as every other wiring of
 * algorithms is (WiredAlgorithms). The first one is what a new hash is made with, which is why a
 * deployment that reorders this list changes what it produces and nothing of what it reads.
 */
final readonly class WiredPasswordAlgorithms implements PasswordAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<PasswordAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @var non-empty-list<PasswordAlgorithmInterface> the same list, narrowed by the check the
     * constructor makes: a provider hashes a password with at least one algorithm
     */
    private array $algorithms;

    /**
     * @param iterable<PasswordAlgorithmInterface> $algorithms at least one, the default first
     */
    public function __construct(iterable $algorithms)
    {
        $wired = new WiredAlgorithms($algorithms);
        if ($wired->algorithms === []) {
            throw new InvalidArgumentException('A provider hashes a password with at least one algorithm.');
        }
        $this->wired = $wired;
        $this->algorithms = $wired->algorithms;
    }

    /**
     * @return non-empty-list<PasswordAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->algorithms;
    }

    public function named(string $name): PasswordAlgorithmInterface
    {
        return (
            $this->wired->named($name) ?? throw new UnsupportedAlgorithmException(sprintf(
                'This provider accounts for no password hash made with "%s"; it accounts for %s.',
                $name,
                implode(', ', $this->wired->names()),
            ))
        );
    }

    public function defaultAlgorithm(): PasswordAlgorithmInterface
    {
        return $this->algorithms[0];
    }
}
