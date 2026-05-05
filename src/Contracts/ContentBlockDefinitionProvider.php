<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Capell\ContentBlocks\Data\ContentBlockDefinitionData;

interface ContentBlockDefinitionProvider
{
    public const TAG = 'capell.content_blocks.definition_providers';

    /**
     * @return iterable<ContentBlockDefinitionData>
     */
    public function definitions(): iterable;
}
