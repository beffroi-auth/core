<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * How a client may reach the authenticator of a credential (WebAuthn Level 3 §5.8.4): the six transports the
 * specification defines, which an authenticator declares at enrolment and a page says in words. An enum
 * because the set is the one the specification fixes and nothing acts on a case; a value it does not define
 * is kept with the credential for the next ceremony, as §5.2.1 asks of a relying party, and is none of these.
 */
enum AuthenticatorTransport: string
{
    case Usb = 'usb';
    case Nfc = 'nfc';
    case Ble = 'ble';
    case SmartCard = 'smart-card';
    case Hybrid = 'hybrid';
    case Internal = 'internal';

    /**
     * The transports of a declaration that the specification defines, in the order of the declaration.
     *
     * @param list<string> $declared
     *
     * @return list<self>
     */
    public static function definedIn(array $declared): array
    {
        $transports = [];
        foreach ($declared as $value) {
            $transport = self::tryFrom($value);
            if ($transport !== null) {
                $transports[] = $transport;
            }
        }

        return $transports;
    }
}
