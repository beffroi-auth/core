<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Webauthn\MetadataService\StatusReportRepository;

/**
 * Leaves the status reports to the policy of the tenant, which admits a model under an alert until a day it names.
 */
final readonly class NoStatusReports implements StatusReportRepository
{
    public function findStatusReportsByAAGUID(string $aaguid): array
    {
        return [];
    }
}
