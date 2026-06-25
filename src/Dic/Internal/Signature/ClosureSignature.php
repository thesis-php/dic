<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Internal\Signature;
use Typhoon\Type\ClosureT;

/**
 * @internal
 */
final class ClosureSignature extends Signature
{
    protected function __construct(ClosureT $signature)
    {
        $variables = new ClosureVariables($signature);

        $parameters = [];

        foreach ($signature->parameters as $position => $signatureParameter) {
            $parameters[] = new ClosureParameter(
                position: $position,
                name: $variables->parameter($signatureParameter),
                parameter: $signatureParameter,
            );
        }

        $this->parameters = $parameters;
    }

    /**
     * @phpstan-ignore property.uninitialized
     */
    public \ReflectionFunction $reflection {
        get {
            if (isset($this->reflection)) {
                return $this->reflection;
            }

            /** @var \Closure */
            $closure = eval("return {$this->header} => null;");

            return $this->reflection = new \ReflectionFunction($closure);
        }
    }

    public null $autowiringMode { get => null; }

    /**
     * @var list<ClosureParameter>
     */
    public readonly array $parameters;

    /**
     * @var non-empty-string
     * @phpstan-ignore property.uninitialized
     */
    public string $header {
        get {
            if (isset($this->header)) {
                return $this->header;
            }

            $header = 'static fn(';

            foreach ($this->parameters as $parameter) {
                if ($parameter->position > 0) {
                    $header .= ', ';
                }

                $header .= \sprintf(
                    '%s%s$%s%s',
                    $parameter->isPassedByReference ? '&' : '',
                    $parameter->isVariadic ? '...' : '',
                    $parameter->name,
                    $parameter->defaultValue === null ? '' : ' = ' . $parameter->defaultValue->print(),
                );
            }

            $header .= ')';

            return $this->header = $header;
        }
    }
}
