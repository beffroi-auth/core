<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use function array_values;
use Beffroi\Core\Domain\Credential\AuthenticatorLogo;
use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Domain\Credential\FidoCertification;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AuthenticatorModelSourceInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\StatusReport;
use Webauthn\MetadataService\StatusReportRepository;

/**
 * The models the FIDO metadata of the store name (architecture §3.4), the ones the console verified under the
 * root it pins or imported from a manufacturer: the description of the statement as the name (FIDO Metadata
 * Statement v3.1.1 §4), its icon as the logo of every page, and the certification its status reports leave the
 * model with. An icon that is not an image of a data: URL is no logo, and the name stands without one.
 */
final readonly class MetadataAuthenticatorModels implements AuthenticatorModelSourceInterface
{
    /**
     * The name of this source, which says that the FIDO Alliance signed what it names.
     */
    public const string SOURCE = 'fido-metadata-service';

    public function __construct(
        private MetadataStatementRepository $statements,
        private StatusReportRepository $reports,
    ) {
    }

    public function model(Uuid $aaguid): ?AuthenticatorModel
    {
        $statement = $this->statements->findOneByAAGUID($aaguid->toRfc4122());
        if ($statement === null || $statement->description === '') {
            return null;
        }

        return new AuthenticatorModel(
            $statement->description,
            self::SOURCE,
            self::logo($statement->icon),
            FidoCertification::lastOf(array_values(array_map(
                static fn (StatusReport $report): string => $report->status,
                $this->reports->findStatusReportsByAAGUID($aaguid->toRfc4122()),
            ))),
        );
    }

    private static function logo(?string $icon): ?AuthenticatorLogo
    {
        if ($icon === null) {
            return null;
        }
        try {
            return AuthenticatorLogo::single($icon);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
