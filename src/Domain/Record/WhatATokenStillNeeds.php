<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Record;

use DateInterval;
use DateTimeImmutable;

/**
 * How long this provider keeps a row an access token still needs, once nothing can be issued from it any
 * more (issue #177): the record a grant was written in, and the login that grant was given on.
 *
 * **It is not a duration a deployment names**, and it is not the lifetime of the row either. A resource
 * server asks three questions of an access token somebody signed in for, and two of them are rows: the
 * session it descends from and the grant it descends from, neither of which it is told anything about by
 * the token itself (AccessTokenInspector). Dropping either one turns a token this provider would have
 * answered for into a token it refuses. So both are worth keeping for exactly as long as an access token
 * of that grant can still be presented, which is two lifetimes and not one: the absolute lifetime of a
 * refresh token family, which no rotation extends, and then the lifetime of the last access token that
 * family can mint, minted at its very last instant.
 *
 * Both numbers are the ones the issuers read, and the wiring hands them here from the same configuration:
 * two places that must agree are one place.
 */
final readonly class WhatATokenStillNeeds
{
    public function __construct(
        private DateInterval $refreshTokenFamilyLifetime,
        private DateInterval $accessTokenLifetime,
    ) {
    }

    /**
     * The instant before which such a row can no longer be the answer to anything, so that a row whose
     * own work ended before it is a row nothing will ever read again.
     */
    public function deadBefore(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->sub($this->refreshTokenFamilyLifetime)->sub($this->accessTokenLifetime);
    }
}
