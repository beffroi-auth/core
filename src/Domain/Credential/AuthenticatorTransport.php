<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * WebAuthn Level 3 §5.8.4
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
