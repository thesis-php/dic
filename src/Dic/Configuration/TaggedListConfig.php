<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;

/**
 * @api
 *
 * @template-covariant T
 * @template-covariant TTag of Tag<T>
 * @extends Config<list<T>>
 */
final class TaggedListConfig extends Config
{
    /**
     * @var list<Ref<T>>
     */
    private array $refs = [];

    /**
     * @internal
     *
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): int $sort
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly string|Tag $tag,
        ?callable $sort,
        Location $declaredAt,
    ) {
        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
        );

        $builder->onTagResolution(function (TaggedRefs $taggedRefs) use ($tag, $sort): void {
            $trs = $taggedRefs->find($tag);

            if ($sort !== null) {
                usort($trs, $sort);
            }

            $this->refs = array_column($trs, 'ref');
        });
    }

    protected LifetimeStrategy $lifetimeStrategy {
        get => LifetimeStrategy::Inferred;
    }

    protected function defaultLabel(): string
    {
        return "tagged({$this->stringifyTag()})";
    }

    /**
     * @return non-empty-string
     */
    private function stringifyTag(): string
    {
        if (\is_string($this->tag)) {
            return $this->tag;
        }

        if ($this->tag instanceof \UnitEnum) {
            return \sprintf('%s::%s', $this->tag::class, $this->tag->name);
        }

        return $this->tag::class;
    }

    protected null $signature { get => null; }

    /** @phpstan-ignore property.phpDocType */
    public null $function { get => null; }

    public null $class { get => null; }

    protected function createFactory(): Factory
    {
        return ValueFactory::from($this->refs);
    }
}
