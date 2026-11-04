<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

enum PasswordChangeRefusal: string
{
    case WrongPassword = 'wrong_password';

    case SameAsBefore = 'same_as_before';

    case NoPasswordHeld = 'no_password_held';

    case FederatedDomain = 'federated_domain';
}
