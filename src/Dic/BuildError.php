<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\Parameter;
use Typhoon\Type;
use Typhoon\Type\Parameter as ClosureParameter;
use function Thesis\Formatter\formatReflectedClass;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * Every error raised by the container. The whole dependency graph is validated
 * eagerly at build, so this is always thrown before resolution — never while a
 * built container hands out services. Construct it through the named static
 * factories; each one carries structured context and builds its own message,
 * so throwing code never assembles message strings itself.
 */
final class BuildError extends \LogicException
{
    /**
     * @internal
     *
     * @param Ref<mixed> $ref
     */
    public static function invalidServiceFactory(Ref $ref, self $previous): self
    {
        return new self(
            \sprintf('Invalid factory for %s: %s', $ref, lcfirst($previous->getMessage())),
            $previous,
        );
    }

    /**
     * @internal
     */
    public static function configurationFrozen(): self
    {
        return new self('Cannot configure the container: configuration is frozen once it starts building');
    }

    /**
     * @internal
     *
     * @param Ref<*> $ref
     * @param Tag<*> $tag
     */
    public static function taggedDuringResolution(Ref $ref, Tag $tag): self
    {
        return new self(\sprintf(
            'Cannot tag %s with "%s" during tag resolution',
            $ref,
            $tag::class,
        ));
    }

    /**
     * @internal
     *
     * @param Ref<mixed> $anchor node where the cycle closes
     * @param list<Dependency> $dependencies cycle edges from the anchor back to itself
     */
    public static function circularDependency(Ref $anchor, array $dependencies): self
    {
        $lastStep = array_key_last($dependencies);

        $lines = ["  {$anchor}"];

        foreach ($dependencies as $step => $dependency) {
            $indent = str_repeat(' ', 2 + 3 * $step);
            $edge = $dependency->path === '' ? '' : "{$dependency->path} → ";
            $marker = $step === $lastStep ? '  ← cycle' : '';
            $lines[] = "{$indent}└─ {$edge}{$dependency->ref}{$marker}";
        }

        return new self(\sprintf(
            <<<'TEXT'
                Circular dependency detected:

                %s

                Break the cycle by removing one of the dependencies above.
                TEXT,
            implode("\n", $lines),
        ));
    }

    /**
     * @internal
     *
     * @param Ref<mixed> $singleton
     * @param non-empty-list<Dependency> $chain edges from the singleton down to the offending non-singleton leaf
     * @param LifetimeStrategy $lifetime the leaf's configured lifetime
     */
    public static function singletonDependsOnScoped(Ref $singleton, array $chain, LifetimeStrategy $lifetime): self
    {
        $lastEdge = array_key_last($chain);

        $lines = ["  {$singleton}"];

        foreach ($chain as $depth => $edge) {
            $indent = str_repeat(' ', 2 + 3 * $depth);
            $path = $edge->path === '' ? '' : "{$edge->path} → ";
            $marker = $depth === $lastEdge ? "  ← {$lifetime->name}" : '';
            $lines[] = "{$indent}└─ {$path}{$edge->ref}{$marker}";
        }

        return new self(\sprintf(
            <<<'TEXT'
                Singleton %s cannot depend on a non-singleton service:

                %s

                Make %1$s scoped (or canBeScoped()), or make the dependency a singleton.
                TEXT,
            $singleton,
            implode("\n", $lines),
        ));
    }

    /**
     * @internal
     *
     * @param Ref<mixed> $ref
     * @param non-empty-string $type
     * @param Ref<mixed> $existing
     */
    public static function duplicateBinding(Ref $ref, string $type, Ref $existing): self
    {
        return new self(\sprintf(
            'Cannot bind %s to type "%s": it is already bound to %s',
            $ref,
            $type,
            $existing,
        ));
    }

    /**
     * @internal
     *
     * @param \ReflectionClass<*> $class
     */
    public static function classNotInstantiable(\ReflectionClass $class): self
    {
        // todo more details + add ref
        return new self(\sprintf('Class "%s" is not instantiable', formatReflectedClass($class)));
    }

    /**
     * @internal
     *
     * @param \ReflectionClass<*> $class
     */
    public static function lazyClassNotInstantiable(\ReflectionClass $class): self
    {
        return new self(\sprintf('Class "%s" cannot be made lazy because it is not instantiable', formatReflectedClass($class)));
    }

    /**
     * @internal
     *
     * @param Ref<mixed> $factory
     */
    public static function factoryNotCallable(Ref $factory): self
    {
        return new self("Factory {$factory} is not callable");
    }

    /**
     * @internal
     *
     * @param Ref<mixed> $ref
     */
    public static function notCallable(Ref $ref): self
    {
        return new self("{$ref} is not callable");
    }

    /**
     * @internal
     */
    public static function factoryMethodNotPublic(\ReflectionMethod $method): self
    {
        return new self(\sprintf(
            'Method "%s" is not public and cannot be used as a service factory',
            formatReflectedFunction($method),
        ));
    }

    /**
     * @internal
     */
    public static function calledMethodNotPublic(\ReflectionMethod $method): self
    {
        return new self(\sprintf(
            'Method "%s" is not public and cannot be called on the service',
            formatReflectedFunction($method),
        ));
    }

    /**
     * @internal
     */
    public static function conflictingAutowireMarkers(): self
    {
        return new self('Cannot combine #[Autowire] and #[DoNotAutowire] on the same target');
    }

    /**
     * @internal
     */
    public static function markersNotAllowedAsArrayElements(): self
    {
        return new self('Autowire, DoNotAutowire and signature parameter markers cannot be used as array elements');
    }

    /**
     * @internal
     */
    public static function unknownSignatureParameter(Signature $signature, int|string|true $positionOrName): self
    {
        return new self(
            $positionOrName === true
                ? 'Cannot set a variadic: the function has no variadic parameter'
                : \sprintf('Unknown parameter "%s"', $positionOrName),
        );
    }

    /**
     * @internal
     */
    public static function cannotAppendToNonArrayVariadic(Parameter $parameter): self
    {
        return self::invalidSignatureArgument($parameter, 'cannot append to a variadic that was set to a non-array value');
    }

    /**
     * @internal
     */
    public static function positionalVariadicAfterNamed(Parameter $parameter): self
    {
        return self::invalidSignatureArgument($parameter, 'cannot set a positional variadic element after a named one');
    }

    /**
     * @internal
     */
    public static function variadicNotAutowirable(Parameter $parameter): self
    {
        return self::invalidSignatureArgument($parameter, 'autowiring is not supported for variadic parameter');
    }

    /**
     * @internal
     */
    public static function unknownClosureParameterMapping(Parameter $parameter): self
    {
        return new self(\sprintf('Unknown closure parameter mapped to "%s"', $parameter));
    }

    /**
     * @internal
     */
    public static function optionalClosureParameterMapping(Parameter $parameter): self
    {
        return new self(\sprintf('Cannot map an optional closure parameter to the non-optional "%s"', $parameter));
    }

    /**
     * @internal
     */
    public static function byReferenceClosureParameterMapping(Parameter $parameter): self
    {
        return new self(\sprintf('Cannot map a by-reference closure parameter to the non-by-reference "%s"', $parameter));
    }

    /**
     * @internal
     */
    public static function cannotAutowireMarkedNotAutowired(Parameter $parameter): self
    {
        return self::cannotAutowire($parameter, 'it is marked as not autowired and has no default value');
    }

    /**
     * @internal
     */
    public static function cannotAutowireNoCandidate(Parameter $parameter): self
    {
        return self::cannotAutowire($parameter, 'no autowiring candidate found');
    }

    /**
     * @internal
     */
    public static function cannotAutowireUnsupportedBindingType(Parameter $parameter, UnsupportedBindingType $previous): self
    {
        return self::cannotAutowire($parameter, lcfirst($previous->getMessage()), $previous);
    }

    /**
     * @internal
     *
     * @param Type<*> $type
     */
    public static function unsupportedBindingType(Type $type, UnsupportedBindingType $previous): self
    {
        return new self(\sprintf('Type "%s" is not supported for binding', Type\stringify($type)), $previous);
    }

    /**
     * @internal
     *
     * @param non-empty-list<Ref<mixed>|ClosureParameter> $candidates
     */
    public static function cannotAutowireAmbiguous(Parameter $parameter, array $candidates): self
    {
        return self::cannotAutowire($parameter, \sprintf(
            'multiple autowiring candidates found: %s',
            implode(', ', array_map(self::describeCandidate(...), $candidates)),
        ));
    }

    private static function cannotAutowire(Parameter $parameter, string $reason, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Cannot autowire "%s": %s', $parameter, $reason), $previous);
    }

    private static function invalidSignatureArgument(Parameter $parameter, string $reason): self
    {
        return new self(\sprintf('Invalid argument "%s": %s', $parameter, $reason));
    }

    /**
     * @param Ref<mixed>|ClosureParameter $candidate
     * @return non-empty-string
     */
    private static function describeCandidate(Ref|ClosureParameter $candidate): string
    {
        if ($candidate instanceof Ref) {
            return (string) $candidate;
        }

        return $candidate->name === null
            ? Type\stringify($candidate->type)
            : \sprintf('%s $%s', Type\stringify($candidate->type), $candidate->name);
    }

    /**
     * @param non-empty-string $message
     */
    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);

        // Point file/line at the throw site rather than the static factory frames.
        // Every factory (and its private message helpers) lives in this file, so the
        // first frame outside it is where the user actually threw.
        foreach ($this->getTrace() as $frame) {
            if (isset($frame['file'], $frame['line']) && $frame['file'] !== __FILE__) {
                $this->file = $frame['file'];
                $this->line = $frame['line'];

                break;
            }
        }
    }
}
