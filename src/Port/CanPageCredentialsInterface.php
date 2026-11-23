<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\CredentialQuestion;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

interface CanPageCredentialsInterface
{
    /**
     * @param class-string<Credential> $kind
     *
     * @return Listing<Credential> in the order CredentialQuestion::narrow() gives, the first enrolled first
     */
    public function pageOfCredentials(User $user, string $kind, CredentialQuestion $question, Pagination $pagination): Listing;
}
