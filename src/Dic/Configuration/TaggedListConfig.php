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
        string|Tag $tag,
        ?callable $sort,
        Location $declaredAt,
    ) {
        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            label: \sprintf('taggedList(%s)', match (true) {
                \is_string($tag) => $tag,
                $tag instanceof \UnitEnum => \sprintf('%s::%s', $tag::class, $tag->name),
                default => $tag::class,
            }),
            declaredAt: $declaredAt,
            defaultLifetimeStrategy: LifetimeStrategy::Inferred,
        );

        $builder->onTagResolution(function (TaggedRefs $taggedRefs) use ($tag, $sort): void {
            $trs = $taggedRefs->find($tag);

            if ($sort !== null) {
                usort($trs, $sort);
            }

            $this->refs = array_column($trs, 'ref');
        });
    }

    protected null $signature { get => null; }

    protected null $reflectionFunction { get => null; }

    protected null $reflectionClass { get => null; }

    protected function createFactory(): Factory
    {
        return ValueFactory::from($this->refs);
    }
}
