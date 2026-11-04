<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

interface AuthenticatorModelSourceInterface
{
    /**
     * @throws RuntimeException when what the source holds cannot be read, never for an AAGUID it does not know
     */
    public function model(Uuid $aaguid): ?AuthenticatorModel;
}
