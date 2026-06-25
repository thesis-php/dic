<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class UnknownRef extends RuntimeError
{
    /**
     * @param Ref<mixed> $ref
     */
    public function __construct(Ref $ref)
    {
        parent::__construct("{$ref} is not registered");
    }
}
