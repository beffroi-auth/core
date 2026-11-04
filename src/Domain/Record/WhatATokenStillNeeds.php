<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Record;

use DateInterval;
use DateTimeImmutable;

/**
 * How long the record of a grant and its login are kept once nothing can be issued from them: the absolute
 * lifetime of a refresh token family, then that of the last access token it can mint.
 */
final readonly class WhatATokenStillNeeds
{
    public function __construct(
        private DateInterval $refreshTokenFamilyLifetime,
        private DateInterval $accessTokenLifetime,
    ) {
    }

    public function deadBefore(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->sub($this->refreshTokenFamilyLifetime)->sub($this->accessTokenLifetime);
    }
}
