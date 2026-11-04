<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\RotationSchedulesInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use function sprintf;

final readonly class KeySuccession
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private KeyStaging $staging,
        private RetirementWindow $window,
        private RotationSchedulesInterface $schedules,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<TenantKey>
     *
     * @throws LogicException when an age does not outlast its retirement window, or no family holds the algorithm
     *                        of a key
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
            if (isset($awaited[$key->algorithm->name()])) {
                continue;
            }
            $age = $this->schedules->of($key->algorithm)->age;
            if (! $key->hasReachedItsAge($now, $age)) {
                continue;
            }
            $this->assertTheAgeOutlasts($tenant, $key, $age, $now);
            $staged[] = $this->staging->stage($tenant, $key->algorithm, $context);
            $awaited[$key->algorithm->name()] = true;
        }

        return $staged;
    }

    /**
     * A shorter age would start each successor retiring before its predecessor had, growing the JWK Set with every
     * rotation.
     *
     * @throws LogicException
     */
    private function assertTheAgeOutlasts(Tenant $tenant, TenantKey $key, DateInterval $age, DateTimeImmutable $now): void
    {
        $window = $this->window->dueAt($tenant, $key->algorithm->keyUse(), $now);
        if ($now->add($age) > $window) {
            return;
        }
        throw new LogicException(sprintf(
            'A key of the "%s" use works for %s and what it holds back lives until %s: the age of a key is longer than that window, or every rotation leaves a key behind in the JWK Set.',
            $key->algorithm->keyUse()->value,
            $now->add($age)->format(DateTimeImmutable::RFC3339),
            $window->format(DateTimeImmutable::RFC3339),
        ));
    }
}
