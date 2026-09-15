<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

/**
 * Authentication Method Reference values, RFC 8176 §2. What goes into the "amr" claim of a token and into
 * the record of a session.
 */
enum AuthenticationMethod: string
{
    case Password = 'pwd';
    case OneTimePassword = 'otp';
    case MultipleFactors = 'mfa';
    case HardwareKey = 'hwk';
    case SoftwareKey = 'swk';
    case UserPresence = 'user';
    case Pin = 'pin';
    case Face = 'face';
    case Fingerprint = 'fpt';
    case Iris = 'iris';
    case Geolocation = 'geo';
    case KnowledgeBased = 'kba';
    case MultipleChannels = 'mca';
    case ProofOfPossession = 'pop';
    case RiskBased = 'rba';
    case Retina = 'retina';
    case SmartCard = 'sc';
    case Sms = 'sms';
    case Telephone = 'tel';
    case Voice = 'vbm';
    case WindowsIntegrated = 'wia';
}
