<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;

/**
 * What a class of a tenant's policy requires of the proofs of a session (decision 0010), under the name a
 * tenant answers to it by: the predicates are the provider's and a tenant names them, it never defines
 * one. One class per predicate, wired into the provider: the policy of a tenant, what a stored policy is
 * read back as and the floor of its logins all read that one wired list, and a predicate the provider
 * gains is a class added to the wiring, never a case added to an enumeration.
 *
 * A predicate holds on more proofs whenever it held on fewer, which is what lets the provider say ahead
 * which ones its logins can prove, and what makes a floor something that can only be raised.
 */
interface AuthenticationPredicateInterface extends NamedAlgorithmInterface
{
    /**
     * A predicate reads which methods were proven and nothing else: not when, not how often. It takes the
     * methods themselves, so that asking what a login *could* prove needs no proofs invented for the
     * occasion, and no date fabricated to carry them.
     *
     * @param list<AuthenticationMethod> $methods
     */
    public function isSatisfiedBy(array $methods): bool;

    /**
     * Whether a credential able to prove these methods has a place under this predicate used as the floor
     * of the logins of a tenant (decision 0025): it has one when the floor reads at least one of them,
     * that is when the credential can be part of a login this floor is met by and be needed in it. What
     * the login page of a tenant offers, and what may be enrolled there; a credential whose methods the
     * floor never reads is a way in the tenant has closed.
     *
     * It is not "would this credential alone meet the floor": under the two factors a password meets
     * nothing by itself and is still half of what meets it. It is not "could a login holding it meet the
     * floor" either, which every credential satisfies as soon as a passkey is held beside it.
     *
     * @param list<AuthenticationMethod> $methods what one authentication with that credential may prove
     *                                            (ProvingCredentialInterface::provableMethods())
     */
    public function admits(array $methods): bool;

    /**
     * Whether this predicate holds on every login there is, that is as soon as anything at all was
     * proven. A policy names a class on one of these, every session having an "acr" (architecture §4.6),
     * and a tenant that names no floor stands on one: it is the bottom a vocabulary and a floor are built
     * from, and the provider knows which of its predicates are one without reading their names.
     */
    public function holdsOnEveryLogin(): bool;
}
