<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use function array_filter;
use function array_values;
use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\SignatureAlgorithmsInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;

/**
 * The wired signature algorithms a tenant holds an active key for: the ones it can sign with now, which a wired
 * algorithm alone does not make.
 */
final readonly class HeldSigningAlgorithms
{
    public function __construct(
        private SignatureAlgorithmsInterface $algorithms,
        private TenantKeyRepositoryInterface $keys,
    ) {
    }

    /**
     * @return list<SignatureAlgorithmInterface> in the order they are wired
     */
    public function of(Tenant $tenant): array
    {
        return array_values(array_filter(
            $this->algorithms->all(),
            fn (SignatureAlgorithmInterface $algorithm): bool => $this->holds($tenant, $algorithm),
        ));
    }

    /**
     * @return list<SignatureAlgorithmInterface> the default algorithm, or nothing when the tenant holds no key for it
     */
    public function defaultOf(Tenant $tenant): array
    {
        $default = $this->algorithms->defaultAlgorithm();

        return $this->holds($tenant, $default) ? [$default] : [];
    }

    public function holds(Tenant $tenant, SignatureAlgorithmInterface $algorithm): bool
    {
        return $this->keys->findActive($tenant, $algorithm) !== null;
    }
}
