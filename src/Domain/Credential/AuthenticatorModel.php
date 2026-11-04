<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

final readonly class AuthenticatorModel
{
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
