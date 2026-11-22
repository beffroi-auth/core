<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Confirmation;

use SensitiveParameter;

final readonly class IssuedAddressConfirmation
{
    /**
     * @param string $claim   the claim of the address it establishes
     * @param string $address the one address it is sent to
     * @param string $code    the digits the person types, of which the confirmation keeps a hash only
     * @param string $value   what the link carries, of which the confirmation keeps a hash only
     */
    public function __construct(
        public AddressConfirmation $confirmation,
        public string $claim,
        public string $address,
        #[SensitiveParameter]
        public string $code,
        #[SensitiveParameter]
        public string $value,
    ) {
    }
}
