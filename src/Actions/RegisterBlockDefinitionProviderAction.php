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
            // A package callback may contribute these definitions before the booted tag consumer.
            // Preserve that registration, while letting the registry reject conflicting definitions.
            // Serialisation preserves nested value types and numeric-string spelling.
            if (serialize($registry->get($definition->key)) === serialize($definition)) {
                continue;
            }

            $registry->register($definition);
        }
    }
}
