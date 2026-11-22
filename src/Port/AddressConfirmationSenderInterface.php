<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Confirmation\IssuedAddressConfirmation;

/**
 * NIST SP 800-63B-4 §4.2.1.2: the code and the link go to the address they establish and to it alone (decision 0048).
 */
interface AddressConfirmationSenderInterface
{
    public function send(IssuedAddressConfirmation $issued): void;
}
