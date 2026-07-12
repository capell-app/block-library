<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Contracts\BlockDemoContentProvider;
use Capell\BlockLibrary\Contracts\BlockFixtureProvider;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockFixtureData;

final class DefaultBlockContentProvider implements BlockDemoContentProvider, BlockFixtureProvider
{
    /**
     * @return iterable<BlockFixtureData>
     */
    public function fixtures(BlockDefinitionData $definition): iterable
    {
        yield new BlockFixtureData(
            key: $definition->key . '.default',
            label: $definition->label . ' default',
            payload: DefaultBlockCatalog::fixturePayload($definition->key),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function demoContent(BlockDefinitionData $definition): array
    {
        return DefaultBlockCatalog::fixturePayload($definition->key);
    }
}
