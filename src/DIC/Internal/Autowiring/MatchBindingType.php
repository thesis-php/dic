<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\ArrayBareT;
use Typhoon\Type\CallableBareT;
use Typhoon\Type\FloatT;
use Typhoon\Type\IntersectionT;
use Typhoon\Type\IntT;
use Typhoon\Type\IterableBareT;
use Typhoon\Type\MixedT;
use Typhoon\Type\NamedObjectT;
use Typhoon\Type\NeverT;
use Typhoon\Type\NullT;
use Typhoon\Type\ObjectT;
use Typhoon\Type\StringT;
use Typhoon\Type\TrueT;
use Typhoon\Type\UnionT;
use Typhoon\Type\Visitor;

/**
 * @internal
 *
 * @extends Visitor\Fallback<bool>
 */
final class MatchBindingType extends Visitor\Fallback
{
    use Visitor\Reduced;

    public function __construct(
        private readonly Type $bindingType,
    ) {}

    #[\Override]
    public function neverT(NeverT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {});
    }

    #[\Override]
    public function nullT(NullT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function nullT(NullT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function trueT(TrueT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function trueT(TrueT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function falseT(Type\FalseT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function falseT(Type\FalseT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function intT(IntT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function intT(IntT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function floatT(FloatT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function intT(IntT $type): mixed
            {
                return true;
            }

            public function floatT(FloatT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function stringT(StringT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function stringT(StringT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function arrayBareT(ArrayBareT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function arrayBareT(ArrayBareT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function objectT(ObjectT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function objectT(ObjectT $type): mixed
            {
                return true;
            }

            public function namedObjectT(NamedObjectT $type): mixed
            {
                return true;
            }
        });
    }

    #[\Override]
    public function namedObjectT(NamedObjectT $type): mixed
    {
        return $this->bindingType->accept(new class ($type->class) extends BindingMatcher {
            /**
             * @param class-string $class
             */
            public function __construct(
                private readonly string $class,
            ) {}

            public function namedObjectT(NamedObjectT $type): mixed
            {
                return is_a($type->class, $this->class, allow_string: true);
            }
        });
    }

    #[\Override]
    public function iterableBareT(IterableBareT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function arrayBareT(ArrayBareT $type): mixed
            {
                return true;
            }

            public function namedObjectT(NamedObjectT $type): mixed
            {
                return is_a($type->class, \Traversable::class, true);
            }
        });
    }

    #[\Override]
    public function callableBareT(CallableBareT $type): mixed
    {
        return $this->bindingType->accept(new class extends BindingMatcher {
            public function arrayBareT(ArrayBareT $type): mixed
            {
                return true;
            }

            public function namedObjectT(NamedObjectT $type): mixed
            {
                return new \ReflectionClass($type->class)->hasMethod('__invoke');
            }
        });
    }

    #[\Override]
    public function unionT(UnionT $type): mixed
    {
        return array_any($type->types, fn(Type $type) => $type->accept($this));
    }

    #[\Override]
    public function intersectionT(IntersectionT $type): mixed
    {
        return array_all($type->types, fn(Type $type) => $type->accept($this));
    }

    #[\Override]
    public function mixedT(MixedT $type): mixed
    {
        return true;
    }

    protected function fallback(Type $type): mixed
    {
        return false;
    }
}
