<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Enrolment;

use SensitiveParameter;

final readonly class IssuedEnrolmentTicket
{
    public function __construct(
        public EnrolmentTicket $ticket,
        #[SensitiveParameter]
        public string $value,
    ) {
    }
}
