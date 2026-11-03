<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Exception\AuthenticatorListRefusedException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AuthenticatorListStoreInterface;
use Beffroi\Core\Port\AuthenticatorModelSourceInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * The models the list of the community names, as the console stored it (architecture §3.4): read once and kept
 * for the life of the process, as the FIDO metadata are (StoredMetadata), so that a worker reads a list stored
 * after it started once it restarts. A provider that never stored one names nothing from it. A stored list was
 * read as such when it was written, so one that does not read is a defect of the store, not a list that names
 * nothing.
 */
final class StoredAuthenticatorList implements AuthenticatorModelSourceInterface
{
    private ?AuthenticatorList $list = null;

    private bool $read = false;

    public function __construct(
        private readonly AuthenticatorListStoreInterface $store,
    ) {
    }

    public function model(Uuid $aaguid): ?AuthenticatorModel
    {
        return $this->list()?->model($aaguid);
    }

    private function list(): ?AuthenticatorList
    {
        if (! $this->read) {
            $document = $this->store->read();
            $this->list = $document === null ? null : self::stored($document);
            $this->read = true;
        }

        return $this->list;
    }

    private static function stored(string $document): AuthenticatorList
    {
        try {
            return AuthenticatorList::fromJson($document);
        } catch (AuthenticatorListRefusedException $exception) {
            throw new RuntimeException(sprintf('The stored list of authenticators does not read: %s', $exception->getMessage()), previous: $exception);
        }
    }
}
