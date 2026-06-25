<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Internal\ShouldNotHappen;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter;

/**
 * @internal
 */
final readonly class ClosureVariables
{
    /**
     * @var \SplObjectStorage<Parameter, non-empty-string>
     */
    private \SplObjectStorage $parameters;

    /**
     * @var non-empty-string
     */
    public string $function;

    /**
     * @var non-empty-string
     */
    public string $appliedArguments;

    public function __construct(ClosureT $signature)
    {
        $used = [];

        foreach ($signature->parameters as $parameter) {
            if ($parameter->name !== null) {
                $used[$parameter->name] = true;
            }
        }

        /** @var \SplObjectStorage<Parameter, non-empty-string> */
        $parameters = new \SplObjectStorage();

        foreach ($signature->parameters as $position => $parameter) {
            $parameters[$parameter] = $parameter->name ?? self::uniqueName('p' . $position, $used);
        }

        $this->parameters = $parameters;
        $this->function = self::uniqueName('f', $used);
        $this->appliedArguments = self::uniqueName('applied', $used);
    }

    /**
     * @return non-empty-string
     */
    public function parameter(Parameter $parameter): string
    {
        return $this->parameters[$parameter] ?? throw new ShouldNotHappen(\sprintf('Unknown parameter %s', $parameter->name ?? 'unnamed'));
    }

    /**
     * @param non-empty-string $base
     * @param array<string, true> $used
     * @return non-empty-string
     */
    private static function uniqueName(string $base, array &$used): string
    {
        $name = $base;
        $suffix = 0;

        while (isset($used[$name])) {
            $name = $base . '_' . $suffix++;
        }

        $used[$name] = true;

        return $name;
    }
}
