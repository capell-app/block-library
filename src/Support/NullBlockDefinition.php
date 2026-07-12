<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;

final class NullBlockDefinition
{
    public static function make(string $key = 'fallback.safe-block'): BlockDefinitionData
    {
        return new BlockDefinitionData(
            key: $key,
            label: 'Safe fallback block',
            description: 'Fallback block used when a registered block cannot be resolved safely.',
            category: 'system',
            view: 'capell-block-library::blocks.fallback',
            safeForPublicOutput: true,
            sourcePackage: 'capell-app/block-library',
            variants: [
                new BlockVariantData(BlockVariantKey::from('default'), 'capell-block-library::blocks.variants.default'),
            ],
        );
    }
}
