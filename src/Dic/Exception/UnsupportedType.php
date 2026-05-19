<?php

declare(strict_types=1);

namespace Thesis\Dic\Exception;

use Typhoon\Type;

/**
 * @api
 */
final class UnsupportedType extends \Exception
{
    public function __construct(Type $type)
    {
        parent::__construct(\sprintf('Type `%s` is not supported', Type\stringify($type)));
    }
}
