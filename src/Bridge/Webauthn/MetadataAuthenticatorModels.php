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
 * FIDO Metadata Statement v3.1.1 §4
 *
 * The icon is the logo of every page, light or dark: webauthn-lib 5.3 reads no "iconDark".
 */
final readonly class MetadataAuthenticatorModels implements AuthenticatorModelSourceInterface
{
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
