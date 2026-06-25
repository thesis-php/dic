<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\DoNotAutowire;

final readonly class OptionalConsumer
{
    public function __construct(
        #[DoNotAutowire]
        public ?Greeter $greeter = null,
    ) {}
}
