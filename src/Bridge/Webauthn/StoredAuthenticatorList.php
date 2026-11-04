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
