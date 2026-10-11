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

/**
 * The "acr" vocabulary of a tenant (decision 0010): an ordered list of classes, each a name on one of the
 * predicates of the provider. No hierarchy: a session satisfies every class whose predicate holds, the
 * order only says which one it is answered with when nothing is requested. What decision 0006 forbids a
 * policy is refused here: "0" (OIDC Core §2 defines it as no confidence at all), a name twice, an empty
 * policy, and a policy without a class on the single factor, since every session has an "acr"
 * (architecture §4.6). What a name may mean is the class's own (AuthenticationContextClass).
 */
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
        $singleFactor = false;
        foreach ($classes as $class) {
            if ($class->value === '0') {
                throw new InvalidArgumentException('"0" is not a class a policy may name: OIDC Core section 2 defines it as no confidence that the same person is there.');
            }
            if (in_array($class->value, $names, true)) {
                throw new InvalidArgumentException(sprintf('An authentication context policy names "%s" once.', $class->value));
            }
            $names[] = $class->value;
            $singleFactor = $singleFactor || $class->predicate === AuthenticationPredicate::SingleFactor;
        }
        if (! $singleFactor) {
            throw new InvalidArgumentException('An authentication context policy names a class on the single factor: every session has an "acr".');
        }
        $this->classes = $classes;
    }

    /**
     * What a tenant that configures nothing speaks, strongest first: the two names of OpenID Connect EAP
     * ACR Values 1.0 and the two levels Keycloak emits by default.
     */
    public static function default(): self
    {
        return new self([
            AuthenticationContextClass::phishingResistantHardware(),
            AuthenticationContextClass::phishingResistant(),
            AuthenticationContextClass::twoFactors(),
            AuthenticationContextClass::singleFactor(),
        ]);
    }

    /**
     * The class of a name of this policy; null for a name the tenant did not declare, which the provider
     * never asserts (OIDC Core §2).
     */
    public function named(string $name): ?AuthenticationContextClass
    {
        foreach ($this->classes as $class) {
            if ($class->value === $name) {
                return $class;
            }
        }

        return null;
    }

    /**
     * What a session is answered with when no class is requested: the first class it satisfies.
     */
    public function classOf(AuthenticationProofs $proofs): AuthenticationContextClass
    {
        foreach ($this->classes as $class) {
            if ($class->isSatisfiedBy($proofs->methods())) {
                return $class;
            }
        }

        throw new InvalidArgumentException('Proofs satisfy no class of the policy, which names the single factor.');
    }

    /**
     * The classes a login able to prove these methods, all of them together, can satisfy: what the
     * discovery announces as "acr_values_supported" and what a step-up may ask for (decision 0010). A
     * predicate never stops holding when a proof is added, so proving everything at once is the question.
     *
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

    /**
     * Same names on the same predicates, in the same order.
     */
    public function equals(self $other): bool
    {
        if (count($this->classes) !== count($other->classes)) {
            return false;
        }

        return array_all(
            $this->classes,
            static fn (AuthenticationContextClass $class, int $index): bool => (
                $class->equals($other->classes[$index])
                && $class->predicate === $other->classes[$index]->predicate
            ),
        );
    }
}
