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
        return strtr($this->pattern, [
            self::TENANT_PLACEHOLDER => $tenant->slug,
        ]);
    }
}
