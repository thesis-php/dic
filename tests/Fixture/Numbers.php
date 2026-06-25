<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class Numbers
{
    /**
     * @var list<int>
     */
    public array $numbers;

    public function __construct(int ...$numbers)
    {
        $this->numbers = array_values($numbers);
    }
}
