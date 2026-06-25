<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\Autowire;

final readonly class QualifiedConsumer
{
    public function __construct(
        #[Autowire('ru')]
        public Greeter $greeter,
    ) {}
}
