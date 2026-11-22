<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Notification;

/**
 * A kind of notification the provider tells a person of.
 */
interface NotificationKindInterface
{
    /**
     * The name the choice of the person holds it under: lowercase letters, digits and underscores.
     */
    public function name(): string;
}
