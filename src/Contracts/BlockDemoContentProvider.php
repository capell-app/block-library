<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Capell\ContentBlocks\Data\BlockDefinitionData;

interface BlockDemoContentProvider
{
    /**
     * @return array<string, mixed>
     */
    public function demoContent(BlockDefinitionData $definition): array;
}
