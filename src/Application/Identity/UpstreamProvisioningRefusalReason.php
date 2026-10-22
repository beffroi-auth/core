<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

/**
 * Why a person an upstream provider named was refused (issue #781), for the audit trail only: whoever is
 * told reads the same message whatever it is, so that the refusal says nothing of who holds an address.
 */
enum UpstreamProvisioningRefusalReason: string
{
    /**
     * The address is outside the domains the provider declares (decision 0029).
     */
    case OutsideItsDomains = 'outside_its_domains';

    /**
     * Another person of the tenant holds the address, and no existing account is linked on its own (spike
     * 12, beffroi-auth/beffroi#85).
     */
    case AddressHeld = 'address_held';

    /**
     * The subject drawn for the person is already the subject or the username of a person of the tenant.
     */
    case DrawnSubjectTaken = 'drawn_subject_taken';

    /**
     * Another sign-in linked that subject of that provider while this one was creating the person.
     */
    case IdentityLinkedMeanwhile = 'identity_linked_meanwhile';
}
