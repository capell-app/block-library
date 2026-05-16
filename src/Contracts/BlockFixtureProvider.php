<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockFixtureData;

interface BlockFixtureProvider
{
    /**
     * @return iterable<BlockFixtureData>
     */
    public function fixtures(BlockDefinitionData $definition): iterable;
}
