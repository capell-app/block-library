<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Contracts;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockFixtureData;

interface BlockFixtureProvider
{
    /**
     * @return iterable<BlockFixtureData>
     */
    public function fixtures(BlockDefinitionData $definition): iterable;
}
