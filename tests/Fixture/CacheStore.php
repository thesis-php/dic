<?php

declare(strict_types=1);

namespace Thesis\Fixture;

enum CacheStore
{
    case Redis;
    case Apcu;
}
