<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonCreated;
use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A person the operator of the instance or an administrator of the tenant creates, the creation an act of their account
 * that names the administrator who did it (decisions 0022 and 0038). An upstream provider creates its people through
 * `JustInTimeProvisioning`, whose act is `person.provisioned`.
 */
final readonly class PersonCreation
{
    public function __construct(
        private UserProvisioner $provisioner,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     * @throws InvalidArgumentException   when the username is empty or longer than 255 characters
     */
    public function create(
        Tenant $tenant,
        string $username,
        RequestContext $context,
        ?EmailAddress $email = null,
        bool $emailVerified = false,
        ?Subject $subject = null,
    ): User {
        return $this->createOnce($tenant, $username, $email, $emailVerified, $subject, $context, null);
    }

    /**
     * @throws UserAlreadyExistsException
     * @throws InvalidArgumentException   when the username is empty or longer than 255 characters
     */
    public function createByAnAdministrator(
        Tenant $tenant,
        string $username,
        Subject $administrator,
        RequestContext $context,
        ?EmailAddress $email = null,
        bool $emailVerified = false,
    ): User {
        return $this->createOnce($tenant, $username, $email, $emailVerified, null, $context, $administrator);
    }

    /**
     * @throws UserAlreadyExistsException
     * @throws InvalidArgumentException
     */
    private function createOnce(
        Tenant $tenant,
        string $username,
        ?EmailAddress $email,
        bool $emailVerified,
        ?Subject $subject,
        RequestContext $context,
        ?Subject $administrator,
    ): User {
        $user = $this->provisioner->create($tenant, $username, $email, $emailVerified, $subject);
        $this->events->dispatch(new PersonCreated($tenant, $user->subject, $user->createdAt, $context, $administrator));

        return $user;
    }
}
