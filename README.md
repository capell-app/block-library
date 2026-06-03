# Capell Block Library

Block Library provides shared block primitives, foundation block views, screenshots, and Filament Builder block definitions that richer content-editing packages can register and render without reaching into each other's internals.

It owns the reusable public Blade blocks and the Builder block catalog, but it still does not own migrations, standalone admin resources, routes, or authoring markup.

The directory remains `packages/block-library` for workspace history, but the package identity is `capell-app/block-library` and the namespace is `Capell\BlockLibrary\`. Keep external references on the Composer name, not the folder name.

Start with [Overview](docs/overview.md) for install impact, surfaces, and screenshot coverage. Screenshot targets for consuming-package diagnostics live in [docs/screenshots.json](docs/screenshots.json).

## Why It Helps Your Capell Workflow

- Gives content packages a shared typed block language and default renderable block catalog without coupling to each other.
- Helps developers add reusable block definitions, variants, and renderer references behind one registry pattern.
- Keeps public block rendering safe by separating trusted definitions from editor state, requests, and database-driven view names.

## Best Used With

- [Layout Builder](../layout-builder/README.md)
- [Content Sections](../content-sections/README.md)
- [Foundation Theme](../foundation-theme/README.md)

## Docs

- [docs index](docs/README.md)
- [overview.md](docs/overview.md)
- [screenshots.json](docs/screenshots.json)

## Current Surface

| Surface                 | Status                                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------------------------ |
| Namespace               | `Capell\BlockLibrary\`                                                                                 |
| Provider                | `Capell\BlockLibrary\Providers\BlockLibraryServiceProvider`                                            |
| Commands                | None                                                                                                   |
| Migrations              | None                                                                                                   |
| Config                  | None                                                                                                   |
| Actions                 | `ListBlockDefinitionsAction`, `RegisterBlockDefinitionProviderAction`, `ResolveBlockDefinitionAction`  |
| Public extension points | `BlockDefinitionProvider::TAG`, `BlockRenderer`, `BlockRegistry`, block fixtures and demo providers    |
| Default blocks          | Hero, content, CTA, accordion, FAQ, features, stats, pricing, tabs, table, team, testimonial, and more |
| Tests                   | Package manifest, registry, provider registration, action resolution, catalog rendering, screenshots   |

## Registering Blocks

Packages register blocks by tagging a `BlockDefinitionProvider` implementation with `BlockDefinitionProvider::TAG`.

```php
use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Data\BlockDefinitionData;

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

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/block-library/tests --configuration=phpunit.xml
```
