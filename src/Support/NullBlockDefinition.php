<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\BlockVariantKey;

final class NullBlockDefinition
{
    public static function make(string $key = 'fallback.safe-block'): BlockDefinitionData
    {
        return new BlockDefinitionData(
            key: $key,
            label: 'Safe fallback block',
            description: 'Fallback block used when a registered block cannot be resolved safely.',
            category: 'system',
            view: 'capell-content-blocks::blocks.fallback',
            safeForPublicOutput: true,
            sourcePackage: 'capell-app/content-blocks',
            variants: [
                new BlockVariantData(BlockVariantKey::from('default'), 'capell-content-blocks::blocks.variants.default'),
            ],
        );
    }
}
