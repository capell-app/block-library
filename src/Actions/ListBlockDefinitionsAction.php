<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListBlockDefinitionsAction
{
    use AsObject;

    /**
     * @return array<string, BlockDefinitionData>
     */
    public function handle(): array
    {
        return resolve(BlockRegistry::class)->all();
    }
}
