<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_all;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function sprintf;

final readonly class AuthenticationContextPolicy
{
    /**
     * @var non-empty-list<AuthenticationContextClass> in the order a session is answered in
     */
    public array $classes;

    /**
     * @param list<AuthenticationContextClass> $classes
     */
    public function __construct(array $classes)
    {
        if ($classes === []) {
            throw new InvalidArgumentException('An authentication context policy names at least one class.');
        }
        $names = [];
        $everyLogin = false;
        foreach ($classes as $class) {
            if ($class->value === '0') {
                throw new InvalidArgumentException('"0" is not a class a policy may name: OIDC Core section 2 defines it as no confidence that the same person is there.');
            }
            if (in_array($class->value, $names, true)) {
                throw new InvalidArgumentException(sprintf('An authentication context policy names "%s" once.', $class->value));
            }
            $names[] = $class->value;
            $everyLogin = $everyLogin || $class->predicate->holdsOnEveryLogin();
        }
        if (! $everyLogin) {
            throw new InvalidArgumentException('An authentication context policy names a class on a predicate that every login satisfies: every session has an "acr".');
        }
        $this->classes = $classes;
    }

    public static function default(): self
    {
        return new self([
            AuthenticationContextClass::phishingResistantHardware(),
            AuthenticationContextClass::phishingResistant(),
            AuthenticationContextClass::twoFactors(),
            AuthenticationContextClass::singleFactor(),
        ]);
    }

    public function named(string $name): ?AuthenticationContextClass
    {
        foreach ($this->classes as $class) {
            if ($class->value === $name) {
                return $class;
            }
        }

        return null;
    }

    public function classOf(AuthenticationProofs $proofs): AuthenticationContextClass
    {
        foreach ($this->classes as $class) {
            if ($class->isSatisfiedBy($proofs->methods())) {
                return $class;
            }
        }

        throw new InvalidArgumentException('Proofs satisfy no class of the policy, which names a class on a predicate that every login satisfies.');
    }

    /**
     * @param non-empty-list<AuthenticationMethod> $methods
     *
     * @return list<AuthenticationContextClass> in the order of the policy
     */
    public function provableWith(array $methods): array
    {
        return array_values(array_filter($this->classes, static fn (AuthenticationContextClass $class): bool => $class->isSatisfiedBy($methods)));
    }

    /**
     * @return non-empty-list<string>
     */
    public function names(): array
    {
        return array_map(static fn (AuthenticationContextClass $class): string => $class->value, $this->classes);
    }

    public function equals(self $other): bool
    {
        if (count($this->classes) !== count($other->classes)) {
            return false;
        }

        return array_all(
            $this->classes,
            static fn (AuthenticationContextClass $class, int $index): bool => (
                $class->equals($other->classes[$index])
                && $class->predicate->name() === $other->classes[$index]->predicate->name()
            ),
        );
    }
}
