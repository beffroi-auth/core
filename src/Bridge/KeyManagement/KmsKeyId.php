<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\KeyManagement;

use Heidi\Core\Domain\Tenant\Tenant;
use function strtr;

/**
 * The identifier of the KMS key of a tenant, from the pattern the configuration gives: "{tenant}" stands
 * for the slug. One pattern, resolved the same way by every sealer.
 */
final readonly class KmsKeyId
{
    public const string TENANT_PLACEHOLDER = '{tenant}';

    public function __construct(
        private string $pattern,
    ) {
    }

    public function of(Tenant $tenant): string
    {
        return $this->forSlug($tenant->slug);
    }

    /**
     * The same identifier from the slug alone, for the one caller that has no tenant to hand over: a data
     * key store is built for the tenant a scope names, and a scope carries a slug and not a row
     * (decision 0018).
     */
    public function forSlug(string $slug): string
    {
        return strtr($this->pattern, [
            self::TENANT_PLACEHOLDER => $slug,
        ]);
    }
}
