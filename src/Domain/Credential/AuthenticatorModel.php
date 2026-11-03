<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * The make and model of an authenticator, as a source of names writes them for the AAGUID it declares (WebAuthn
 * Level 3 §6): the name, the logo when the source has one, the FIDO certification when the source is the FIDO
 * metadata, and the source, which says whether anybody signed what it names. Nothing of it is proven of the
 * credential that declared the AAGUID (ModelDeclaringCredentialInterface).
 */
final readonly class AuthenticatorModel
{
    /**
     * @param string $name as its source writes it, never translated nor corrected
     * @param string $source the name the source gives itself
     */
    public function __construct(
        public string $name,
        public string $source,
        public ?AuthenticatorLogo $logo = null,
        public ?FidoCertification $certification = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('A model of authenticator has a name.');
        }
        if ($source === '') {
            throw new InvalidArgumentException('A model of authenticator is named by a source that has a name.');
        }
    }
}
