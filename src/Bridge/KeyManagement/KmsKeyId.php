<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Domain\Tenant\Tenant;
use function strtr;

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

    public function forSlug(string $slug): string
    {
        return strtr($this->pattern, [
            self::TENANT_PLACEHOLDER => $slug,
        ]);
    }
}
