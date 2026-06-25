<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Error\TaggedAfterResolution;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\EnGreeter;
use Thesis\Fixture\Greeter;
use Thesis\Fixture\GreeterTag;

#[Covers(Tags::class)]
final class TagsTest
{
    #[Test]
    public function onResolveListenerReceivesCollectedTags(): void
    {
        $tags = new Tags();
        $tags->add(self::ref(), new GreeterTag());

        $found = null;
        $tags->onResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
            $found = $taggedRefs->find(GreeterTag::class);
        });

        $tags->resolve();

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    #[Test]
    public function addingTagAfterResolveIsRejected(): void
    {
        $tags = new Tags();
        $tags->resolve();

        Expect::exception(TaggedAfterResolution::class);

        $tags->add(self::ref(), new GreeterTag());
    }

    /**
     * @return Ref<Greeter>
     */
    private static function ref(): Ref
    {
        return new ValueConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            value: new EnGreeter(),
            declaredAt: Location::caller(),
        );
    }
}
