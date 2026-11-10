<?php

declare(strict_types=1);

namespace Thesis\Fixture;

/**
 * A user class extending an internal one: PHP cannot make it lazy.
 *
 * @extends \ArrayObject<int, Counter>
 */
final class CounterList extends \ArrayObject {}
