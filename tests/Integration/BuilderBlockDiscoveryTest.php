<?php

declare(strict_types=1);

use Capell\ContentBlocks\Contracts\FilamentBuilderBlock;
use Capell\ContentBlocks\Enums\BuilderBlockTarget;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\ContentBlocks\Support\BuilderBlockDiscovery;
use Capell\ContentBlocks\Support\BuilderBlockRegistry;
use Capell\ContentBlocks\Tests\Fixtures\BuilderBlocks\HeroBuilderBlock;
use Capell\ContentBlocks\Tests\Fixtures\BuilderBlocks\LegacyBuilderBlock;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Filesystem\Filesystem;

it('binds the builder block registry and discovery separately from typed content blocks', function (): void {
    expect(resolve(BuilderBlockRegistry::class))->toBeInstanceOf(BuilderBlockRegistry::class)
        ->and(resolve(BuilderBlockDiscovery::class))->toBeInstanceOf(BuilderBlockDiscovery::class)
        ->and(resolve(BlockRegistry::class))->toBeInstanceOf(BlockRegistry::class);
});

it('registers explicit builder block classes and exposes filament block instances', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->register(HeroBuilderBlock::class);

    $blocks = $discovery->filamentBlocks();

    expect($registry->get('hero', BuilderBlockTarget::AdminFilament))->toBe(HeroBuilderBlock::class)
        ->and($blocks)->toHaveCount(1)
        ->and($blocks[0])->toBeInstanceOf(Block::class)
        ->and($blocks[0]->getName())->toBe('hero');
});

it('registers legacy builder block classes by their old static contract', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->register(LegacyBuilderBlock::class);

    $blocks = $discovery->filamentBlocks();

    expect($registry->get('legacy', BuilderBlockTarget::AdminFilament))->toBe(LegacyBuilderBlock::class)
        ->and($blocks)->toHaveCount(1)
        ->and($blocks[0])->toBeInstanceOf(Block::class)
        ->and($blocks[0]->getName())->toBe('legacy');
});

it('discovers concrete filament builder block implementations from registered paths', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->registerDiscoverableBlocks(
        __DIR__ . '/../Fixtures/BuilderBlocks',
        'Capell\\ContentBlocks\\Tests\\Fixtures\\BuilderBlocks',
    );

    expect($discovery->filamentBlocks())->toHaveCount(2)
        ->and($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
            'hero' => HeroBuilderBlock::class,
            'legacy' => LegacyBuilderBlock::class,
        ]);
});

it('caches discovered builder block classes for warm starts', function (): void {
    $filesystem = new Filesystem;
    $cachePath = sys_get_temp_dir() . '/capell-content-blocks-builder-blocks.php';
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, $filesystem, $cachePath);

    $filesystem->delete($cachePath);

    $discovery->registerDiscoverableBlocks(
        __DIR__ . '/../Fixtures/BuilderBlocks',
        'Capell\\ContentBlocks\\Tests\\Fixtures\\BuilderBlocks',
    );

    $discovery->cacheBlocks();

    try {
        expect(require $cachePath)->toBe([
            'hero' => HeroBuilderBlock::class,
            'legacy' => LegacyBuilderBlock::class,
        ]);
    } finally {
        $filesystem->delete($cachePath);
    }
});

it('rejects non builder block classes', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $invalidBlock = stdClass::class;

    $discovery->register($invalidBlock);
})->throws(InvalidArgumentException::class, 'must implement ' . FilamentBuilderBlock::class);
