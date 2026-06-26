<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Error;
use Thesis\Dic\Internal\Signature\Parameter;

/**
 * @api
 */
final class CannotAutowire extends Error
{
    /**
     * @internal
     */
    public function __construct(
        Parameter $parameter,
        string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: \sprintf('Cannot autowire "%s": %s', $parameter, $reason),
            previous: $previous,
        );
    }
}
