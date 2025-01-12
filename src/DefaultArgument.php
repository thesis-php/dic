<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 */
enum DefaultArgument
{
    case Value;
}

const defaultArgument = DefaultArgument::Value;
