<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class CounterAndNumbers
{
    /**
     * @var list<int>
     */
    public array $numbers;

    public function __construct(
        public Counter $counter,
        int ...$numbers,
    ) {
        $this->numbers = array_values($numbers);
    }
}
