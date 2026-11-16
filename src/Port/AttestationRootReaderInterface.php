<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\AttestationRoot;
use Beffroi\Core\Exception\InvalidArgumentException;

interface AttestationRootReaderInterface
{
    /**
     * @throws InvalidArgumentException when the PEM holds no certificate, or the certificate of no certificate authority
     */
    public function read(string $pem): AttestationRoot;
}
