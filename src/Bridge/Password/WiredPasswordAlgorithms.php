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

final readonly class WiredPasswordAlgorithms implements PasswordAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<PasswordAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @var non-empty-list<PasswordAlgorithmInterface>
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
