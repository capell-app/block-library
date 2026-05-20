# Capell Content Blocks

Content Blocks provides shared block primitives that richer content-editing packages can register and render without reaching into each other's internals.

It is intentionally small: a typed block definition DTO, a block registry, provider contracts, and actions for registering/listing/resolving blocks. It does not own migrations, admin resources, frontend output, or authoring markup.

Start with [Overview](docs/overview.md) for install impact, surfaces, and screenshot coverage. Screenshot targets for consuming-package diagnostics live in [docs/screenshots.json](docs/screenshots.json).

## Current Surface

| Surface                 | Status                                                                                                |
| ----------------------- | ----------------------------------------------------------------------------------------------------- |
| Namespace               | `Capell\ContentBlocks\`                                                                               |
| Provider                | `Capell\ContentBlocks\Providers\ContentBlocksServiceProvider`                                         |
| Commands                | None                                                                                                  |
| Migrations              | None                                                                                                  |
| Config                  | None                                                                                                  |
| Actions                 | `ListBlockDefinitionsAction`, `RegisterBlockDefinitionProviderAction`, `ResolveBlockDefinitionAction` |
| Public extension points | `BlockDefinitionProvider::TAG`, `BlockRenderer`, `BlockRegistry`, block fixtures and demo providers   |
| Tests                   | Package manifest, registry, provider registration, action resolution                                  |

## Registering Blocks

Packages register blocks by tagging a `BlockDefinitionProvider` implementation with `BlockDefinitionProvider::TAG`.

```php
use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Data\BlockDefinitionData;

final class MarketingBlockProvider implements BlockDefinitionProvider
{
    public function definitions(): iterable
    {
        yield new BlockDefinitionData(
            key: 'marketing.hero',
            label: 'Marketing hero',
            description: 'A campaign-ready hero block.',
            category: 'marketing',
            view: 'vendor-package::blocks.marketing-hero',
            defaults: ['alignment' => 'center'],
        );
    }
}
```

Block views must render ordinary public HTML. Authoring metadata, selectors, model IDs, signed URLs, and editor scripts belong behind the authenticated frontend authoring beacon, not in block definitions or public output.

## Block Definitions

`BlockDefinitionData` remains backwards compatible with the original `key`, `label`, `description`, `category`, `view`, and `defaults` shape. New packages can also provide:

- per-block variants through `BlockVariantData` and `BlockVariantKey` slug value objects;
- structured setting definitions with translated label/help keys, defaults, grouping, responsive fallbacks, and accessibility rules;
- content and accessibility contracts for required fields, item limits, CTA rules, image ratios, alt/decorative-image intent, semantic rules, and keyboard expectations;
- context-separated `PublicBlockViewReference` and `AdminPreviewBlockViewReference`;
- class-string fixture/demo providers, screenshots, compatibility metadata, and source package metadata.

Public views are trusted PHP definitions only. Do not read view names from editor state, database meta, fixtures, or request data.

## Registry Cache Safety

Registry manifests should contain structural metadata only. Localized labels/help text should be translation keys or resolved for the current admin locale at render time.

Compiled manifests must be written atomically and validated against currently installed packages, provider classes, fixture/demo provider classes, and trusted view contexts before use. If compilation fails and no valid manifest exists, callers should fall back to the safe built-in fallback definition and surface an admin/system health warning.
