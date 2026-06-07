<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Data\BlockDefinitionData;

final class DefaultBlockDefinitionProvider implements BlockDefinitionProvider
{
    /**
     * @return iterable<BlockDefinitionData>
     */
    public function definitions(): iterable
    {
        foreach (DefaultBlockCatalog::keys() as $key) {
            yield new BlockDefinitionData(
                key: $key,
                label: DefaultBlockCatalog::label($key),
                description: DefaultBlockCatalog::description($key),
                category: 'foundation',
                view: DefaultBlockCatalog::viewName($key),
                safeForPublicOutput: true,
                sourcePackage: 'capell-app/block-library',
                screenshots: DefaultBlockCatalog::screenshots($key),
            );
        }
    }
}
