<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function is_string;
use function sprintf;

/**
 * What a person may change of themselves, in this tenant (issue #452): the names of the claims its people
 * say for themselves, and nothing else.
 *
 * **A rule of the tenant and never an option of the deployment**, because the answer differs by
 * organisation and not by installation: a tenant whose directory owns the data must refuse every one of
 * them, a tenant that creates its people by hand may open all of them, and the same instance serves both.
 *
 * **Nothing by default.** A provider that has not been told what its people may say of themselves says
 * nothing is theirs to say: opening a claim is a decision somebody takes, where closing one would be a
 * decision nobody took.
 */
final readonly class SelfServedClaims
{
    /**
     * @var list<string> the names, each once, in the order they were declared
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

    /**
     * The tenant that lets a person change nothing of themselves, which is what a tenant says until it
     * says otherwise.
     */
    public static function none(): self
    {
        return new self();
    }

    /**
     * The document a column holds it as: a JSON list and not an object, because what it holds is a set of
     * names in the order they were declared and nothing is keyed by them. A tenant that lets a person
     * change nothing writes an empty list, which is what that value is.
     *
     * @return list<string>
     */
    public function toDocument(): array
    {
        return $this->names;
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document holds something that is not a claim name
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
     * @throws InvalidArgumentException when this tenant does not let a person say that of themselves
     */
    public function assertOpens(string $claim): void
    {
        if (! $this->opens($claim)) {
            throw new InvalidArgumentException(sprintf('This tenant does not let a person change "%s" of themselves.', $claim));
        }
    }
}
