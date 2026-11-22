<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Notification;

/**
 * A kind whose channels the person chooses. No choice narrows a kind that is not one: every channel that carries it
 * tells them, as a kind a rule sends to every address must (NIST SP 800-63B-4 §4.6).
 */
interface ChoosableNotificationKindInterface extends NotificationKindInterface
{
}
