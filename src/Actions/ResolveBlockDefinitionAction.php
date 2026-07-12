<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static BlockDefinitionData run(string $key)
 */
final class ResolveBlockDefinitionAction
{
    use AsObject;

    public function handle(string $key): BlockDefinitionData
    {
        return resolve(BlockRegistry::class)->getOrFail($key);
    }
}
