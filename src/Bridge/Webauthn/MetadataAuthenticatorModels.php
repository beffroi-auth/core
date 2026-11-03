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
 * The models the FIDO metadata of the store name (architecture §3.4), a blob the console verified under the
 * root it pins or a statement it imported as given: the description of the statement as the name (FIDO
 * Metadata Statement v3.1.1 §4), its icon, the one §4 gives a light page, as the logo of every page, webauthn-lib
 * 5.3 reading no "iconDark", and the certification its status reports leave the model with. An icon that is
 * not an image of a data: URL is no logo, and the name stands without one.
 */
final readonly class MetadataAuthenticatorModels implements AuthenticatorModelSourceInterface
{
    /**
     * The name of this source: the FIDO metadata of the store, whether the service signed them or the console
     * imported them.
     */
    public const string SOURCE = 'fido-metadata';

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
