<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Contracts;

use Capell\BlockLibrary\Data\BlockDefinitionData;

interface BlockDemoContentProvider
{
    /**
     * @return array<string, mixed>
     */
    public function demoContent(BlockDefinitionData $definition): array;
}
