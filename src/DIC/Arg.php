<?php

declare(strict_types=1);

namespace Thesis\DIC;

enum Arg
{
    case Default;
    case Autowire;
}

const defaultValue = Arg::Default;
const autowire = Arg::Autowire;
