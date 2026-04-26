<?php

declare(strict_types=1);

namespace Thesis\Dic\Exception;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class UnknownRef extends \LogicException
{
    /**
     * @param Ref<*> $ref
     */
    public function __construct(Ref $ref)
    {
        parent::__construct("{$ref} is not registered");
    }
}
