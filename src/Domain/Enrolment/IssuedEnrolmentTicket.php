<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Enrolment;

use SensitiveParameter;

/**
 * The ticket and the value it was drawn with, handed back once to whoever issued it (issue #613): the row
 * holds a hash of that value and nothing can read it again, so this is the one moment it exists. What a
 * caller does with it, print it in a terminal or show it on a screen, is the caller's.
 */
final readonly class IssuedEnrolmentTicket
{
    public function __construct(
        public EnrolmentTicket $ticket,
        #[SensitiveParameter]
        public string $value,
    ) {
    }
}
