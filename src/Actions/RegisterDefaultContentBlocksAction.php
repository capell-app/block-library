<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Support\ContentBlockRegistry;
use Capell\ContentBlocks\Support\DefaultContentBlockDefinitionProvider;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterDefaultContentBlocksAction
{
    use AsObject;

    public function handle(ContentBlockRegistry $registry): void
    {
        RegisterContentBlockDefinitionProviderAction::run(
            registry: $registry,
            provider: resolve(DefaultContentBlockDefinitionProvider::class),
        );
    }
}
