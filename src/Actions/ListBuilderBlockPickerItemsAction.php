<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\BuilderBlockPickerItemData;
use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static list<BuilderBlockPickerItemData> run(?string $search = null)
 */
final class ListBuilderBlockPickerItemsAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly BuilderBlockDiscovery $discovery,
        private readonly BuilderBlockRegistry $registry,
    ) {}

    /**
     * @return list<BuilderBlockPickerItemData>
     */
    public function handle(?string $search = null): array
    {
        $this->discovery->filamentBlocks();

        $items = [];

        foreach ($this->registry->allForTarget(BuilderBlockTarget::AdminFilament) as $key => $builderBlockClass) {
            if (! is_string($builderBlockClass) || ! class_exists($builderBlockClass)) {
                continue;
            }

            if (! in_array($key, DefaultBlockCatalog::keys(), true)) {
                continue;
            }

            /** @var class-string $builderBlockClass */
            $items[] = new BuilderBlockPickerItemData(
                key: $key,
                label: DefaultBlockCatalog::label($key),
                description: DefaultBlockCatalog::description($key),
                category: 'foundation',
                icon: 'heroicon-' . DefaultBlockCatalog::icon($key)->value,
                searchTerms: DefaultBlockCatalog::searchTerms($key),
                builderBlockClass: $builderBlockClass,
            );
        }

        usort(
            $items,
            static fn (BuilderBlockPickerItemData $first, BuilderBlockPickerItemData $second): int => $first->label <=> $second->label,
        );

        $normalizedSearch = strtolower(trim((string) $search));

        if ($normalizedSearch === '') {
            return $items;
        }

        return array_values(array_filter(
            $items,
            static fn (BuilderBlockPickerItemData $item): bool => str_contains(implode(' ', $item->searchTerms), $normalizedSearch),
        ));
    }
}
