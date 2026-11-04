<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function is_string;
use function sprintf;

final readonly class SelfServedClaims
{
    /**
     * @var list<string> each once, in the order they were declared
     */
    public array $names;

    /**
     * @param list<string> $names
     */
    public function __construct(array $names = [])
    {
        $kept = [];
        foreach ($names as $name) {
            if ($name === '') {
                throw new InvalidArgumentException('A claim a person may change of themselves has a name.');
            }
            if (! in_array($name, $kept, true)) {
                $kept[] = $name;
            }
        }
        $this->names = $kept;
    }

    public static function none(): self
    {
        return new self();
    }

    /**
     * @return list<string>
     */
    public function toDocument(): array
    {
        return $this->names;
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(array $document): self
    {
        $names = [];
        foreach ($document as $name) {
            if (! is_string($name)) {
                throw new InvalidArgumentException('The claims a person may change of themselves are names.');
            }
            $names[] = $name;
        }

        return new self($names);
    }

    public function opens(string $claim): bool
    {
        return in_array($claim, $this->names, true);
    }

    public function equals(self $other): bool
    {
        return $this->names === $other->names;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertOpens(string $claim): void
    {
        if (! $this->opens($claim)) {
            throw new InvalidArgumentException(sprintf('This tenant does not let a person change "%s" of themselves.', $claim));
        }
    }
}
