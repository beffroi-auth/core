<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\Claim\ClaimInterface;

/**
 * The claims a person is described with in this deployment, which the wiring gives (issue #452), the one
 * source of truth over what each of them holds: a console holds the profile it says of a person to the set
 * of each claim, as the page of the account does (issue #846).
 */
interface ClaimsInterface
{
    /**
     * The claim that answers that name of OIDC Core §5.1, null when nothing wired answers it.
     */
    public function named(string $name): ?ClaimInterface;
}
