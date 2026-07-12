<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Contracts;

use Capell\BlockLibrary\Data\BlockDefinitionData;

interface BlockDefinitionProvider
{
    public const string TAG = 'capell.content_blocks.definition_providers';

    /**
     * @return iterable<BlockDefinitionData>
     */
    public function definitions(): iterable;
}
