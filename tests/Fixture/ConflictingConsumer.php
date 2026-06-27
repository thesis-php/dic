<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;

final readonly class ConflictingConsumer
{
    public function __construct(
        #[Autowire]
        #[DoNotAutowire]
        public Cache $cache,
    ) {}
}
