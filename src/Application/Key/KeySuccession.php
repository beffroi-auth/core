<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use function sprintf;

/**
 * The step of the life of a key nobody asks for (issue #455, architecture §3.3): an active key that has
 * worked for the age a deployment gives one gets a successor of its own algorithm. The counterpart of
 * KeyStaging, KeyTakeOver and KeyRetirement.
 *
 * **Staged and not rotated**, because that is the whole point of the staging window: a relying party sees
 * the new "kid" in the JWK Set before anything is signed with it, and the two steps beside this one finish
 * the work, the take-over when the window ends and the retirement when the old key has nothing left alive.
 * A step that rotated would make a rotation without an outage out of a rotation nobody watched.
 *
 * **Idempotent by what it looks at and not by a flag**: a key gets a successor only when its algorithm has
 * no staged key, so the tick that follows finds the one this call staged and stages nothing. The reading is
 * one pass over the keys in service, the staged ones included.
 */
final readonly class KeySuccession
{
    /**
     * @param DateInterval $age how long a key works before a successor is staged for it, counted from its activation
     */
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private KeyStaging $staging,
        private RetirementWindow $window,
        private DateInterval $age,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<TenantKey> the successors staged by this call
     *
     * @throws LogicException if the age is not longer than the retirement window of what the key is for:
     *                        each successor would then start retiring before the previous key had
     *                        retired, and the JWK Set would grow with every rotation
     */
    public function stageSuccessorsDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $inService = $this->keys->findInService($tenant);
        $awaited = [];
        foreach ($inService as $key) {
            if ($key->isStaged()) {
                $awaited[$key->algorithm->name()] = true;
            }
        }
        $staged = [];
        foreach ($inService as $key) {
            if (! $key->hasReachedItsAge($now, $this->age) || isset($awaited[$key->algorithm->name()])) {
                continue;
            }
            $this->assertTheAgeOutlasts($tenant, $key, $now);
            $staged[] = $this->staging->stage($tenant, $key->algorithm, $context);
            $awaited[$key->algorithm->name()] = true;
        }

        return $staged;
    }

    /**
     * The age has to outlast what a key of that use holds back, and the comparison is made here because
     * here is the one place both numbers exist: the age is the deployment's and the window is the tenant's,
     * read from the kinds of artifact that name the use. A deployment that got it wrong hears it on the
     * first tick rather than finding its JWK Set grown a quarter later.
     *
     * @throws LogicException
     */
    private function assertTheAgeOutlasts(Tenant $tenant, TenantKey $key, DateTimeImmutable $now): void
    {
        $window = $this->window->dueAt($tenant, $key->algorithm->keyUse(), $now);
        if ($now->add($this->age) > $window) {
            return;
        }
        throw new LogicException(sprintf(
            'A key of the "%s" use works for %s and what it holds back lives until %s: the age of a key is longer than that window, or every rotation leaves a key behind in the JWK Set.',
            $key->algorithm->keyUse()->value,
            $now->add($this->age)->format(DateTimeImmutable::RFC3339),
            $window->format(DateTimeImmutable::RFC3339),
        ));
    }
}
