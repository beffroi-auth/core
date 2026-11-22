<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Notification;

/**
 * A kind whose channels the person chooses. A kind that is not one reaches them by every channel the deployment turns
 * on, whatever they chose, as a kind a rule sends to every address must (NIST SP 800-63B-4 §4.6).
 */
interface ChoosableNotificationKindInterface extends NotificationKindInterface
{
}
