<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;

/**
 * One message whatever the reason: the address is a personal datum, logged wherever the exception is, and the
 * reason would tell whether somebody of the tenant holds it.
 */
final class UpstreamProvisioningRefusedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly UpstreamProvisioningRefusalReason $reason,
    ) {
        parent::__construct('The person an upstream provider named is not one this provider creates or writes.');
    }
}
