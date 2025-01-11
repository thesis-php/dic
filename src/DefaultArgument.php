<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 * @implements Definition<mixed>
 */
enum DefaultArgument implements Definition
{
    case Value;
}

const defaultArgument = DefaultArgument::Value;
