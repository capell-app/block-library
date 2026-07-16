<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class RegisterBlockDefinitionProviderAction
{
    use AsFake;
    use AsObject;

    public function handle(BlockRegistry $registry, BlockDefinitionProvider $provider): void
    {
        foreach ($provider->definitions() as $definition) {
            $registry->register($definition);
        }
    }
}
