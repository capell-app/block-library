<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\Admin\Contracts\Widgets\BlockPickerMetadataProvider;
use Capell\Admin\Data\Widgets\BlockPickerItemMetadataData;
use Capell\BlockLibrary\Actions\ListBuilderBlockPickerItemsAction;

/**
 * Adapts Block Library's existing {@see ListBuilderBlockPickerItemsAction}
 * output into Core Admin's neutral, optional block-picker metadata contract,
 * so the shared block picker in `ContentBuilder` can render
 * Block Library's labels, descriptions, categories, icons, and search terms.
 *
 * Core never imports this class. Block Library registers it against Core's
 * contract from its own service provider by tagging it with
 * {@see BlockPickerMetadataProvider::TAG}.
 */
final class BlockPickerMetadataAdapter implements BlockPickerMetadataProvider
{
    /**
     * @return array<string, BlockPickerItemMetadataData>
     */
    public function blockPickerMetadata(): array
    {
        $metadata = [];

        foreach (ListBuilderBlockPickerItemsAction::run() as $item) {
            $metadata[$item->key] = new BlockPickerItemMetadataData(
                label: $item->label,
                description: $item->description,
                category: $item->category,
                icon: $item->icon,
                searchTerms: $item->searchTerms,
            );
        }

        return $metadata;
    }
}
