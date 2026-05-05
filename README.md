# Content Blocks

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

## What This Plugin Adds

Content Blocks adds reusable content records that can be managed in Filament and rendered through Mosaic-style assets and configurators.

- Content block Filament resource.
- Reusable block creation, replication, and form mutation actions.
- Default, hero, testimonial, accordion, call to action, comparison, counter, divider, FAQ, features, logos, pricing, stats, table, tabs, team, and timeline block configurators.
- Registry-backed block definitions for admin discovery and future package registration.
- Asset relation manager support.
- Content select and repeater form components.

## Why It Matters

**For developers:** Gives packages a structured content model with typed configurators and asset relations rather than pushing shared content into page JSON.

**For teams:** Lets editors manage reusable pieces of content once and place them across structured websites.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Content blocks admin index.
- Create/edit content block form.
- Content block asset relation manager.
- Widget or page selector using a content block.

## Technical Shape

- ContentBlocksServiceProvider registers the package.
- Migration creates content_blocks.
- Model: ContentBlock.
- Filament resource: ContentBlockResource.
- Actions create, replicate, and mutate content state.
- Mosaic support component handles content block assets.
- Content block definitions are loaded from the default provider plus any package providers tagged with `ContentBlockDefinitionProvider::TAG`.

## Extending From Another Package

Other packages can add their own content blocks without changing this package. Create a provider that implements `ContentBlockDefinitionProvider`, return one or more `ContentBlockDefinitionData` objects, and tag that provider in the package service provider.

```php
use Capell\ContentBlocks\Contracts\ContentBlockDefinitionProvider;
use Capell\ContentBlocks\Data\ContentBlockDefinitionData;
use Filament\Support\Icons\Heroicon;

final class VideoBlockDefinitionProvider implements ContentBlockDefinitionProvider
{
    /**
     * @return iterable<ContentBlockDefinitionData>
     */
    public function definitions(): iterable
    {
        return [
            new ContentBlockDefinitionData(
                key: 'video',
                label: __('capell-video-block::block.video.label'),
                description: __('capell-video-block::block.video.description'),
                icon: Heroicon::OutlinedPlayCircle,
                group: 'media',
                configurator: VideoContentBlockConfigurator::class,
                component: 'capell-video-block::content-block.video',
            ),
        ];
    }
}
```

```php
use Capell\ContentBlocks\Contracts\ContentBlockDefinitionProvider;

public function register(): void
{
    $this->app->tag([
        VideoBlockDefinitionProvider::class,
    ], ContentBlockDefinitionProvider::TAG);
}
```

The package owns its configurator, views, translations, and any dependencies. Content Blocks discovers the tagged provider when the local app boots, registers the block definition, contributes the configurator to the admin surface, and uses the definition component when rendering frontend assets.

## Data Model

- content_blocks stores reusable content and metadata.
- Content block factories and type factories support tests and demos.
- Assets are managed through relation manager behaviour.
- Deletion behaviour for reused content should be verified before removing shared records.

## Future Optional Blocks

Advanced blocks should live in their own packages and register into the content block registry:

- `capell-block-before-after`
- `capell-block-code-snippet`
- `capell-block-map`
- `capell-block-video`
- `capell-block-speed-dial`
- `capell-block-parallax`
- `capell-block-document-list`
- `capell-block-media-gallery`
- `capell-block-posts`
- `capell-block-contact-form`

## Install Impact

- Adds content_blocks table.
- Adds content block admin resource.
- Adds Filament form components for choosing content blocks.
- No public route is registered by this package.

## Commands

- None proven in this package directory.

## Admin And Access

- ContentBlockResource (packages/content-blocks/src/Filament/Resources/ContentBlocks/ContentBlockResource.php)
- CreateContentBlock (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/CreateContentBlock.php)
- EditContentBlock (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/EditContentBlock.php)
- ListContentBlocks (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/ListContentBlocks.php)

- None proven in this package directory.

## Common Pitfalls

- Check where a content block is reused before deleting it.
- Keep configurator types aligned with registered content types.
- Run migrations before opening the resource.

## Quick Start

1. Install the package with `composer require capell-app/content-blocks`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../mosaic/README.md](../mosaic/README.md)
