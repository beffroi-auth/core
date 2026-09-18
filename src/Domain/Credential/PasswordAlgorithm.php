<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

/**
 * Every password hash names its algorithm (decision 0002); the cases beyond argon2id exist for imports.
 */
enum PasswordAlgorithm: string
{
    case Argon2id = 'argon2id';
    case Argon2i = 'argon2i';
    case Bcrypt = 'bcrypt';
    case Pbkdf2Sha256 = 'pbkdf2-sha256';
    case Pbkdf2Sha512 = 'pbkdf2-sha512';
}
