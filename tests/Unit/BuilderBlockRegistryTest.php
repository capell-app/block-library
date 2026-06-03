<?php

declare(strict_types=1);

use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;

it('registers builder block components by target', function (): void {
    $registry = new BuilderBlockRegistry;

    $registry->register('content', BuilderBlockTarget::AdminFilament, 'Vendor\\Package\\BlockLibrary');
    $registry->register('content', BuilderBlockTarget::FrontendBlade, 'vendor-package::blocks.content');
    $registry->register('cards', 'admin.filament', 'Vendor\\Package\\CardsBlock');

    expect($registry->get('content', BuilderBlockTarget::AdminFilament))->toBe('Vendor\\Package\\BlockLibrary')
        ->and($registry->get('content', BuilderBlockTarget::FrontendBlade))->toBe('vendor-package::blocks.content')
        ->and($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
            'content' => 'Vendor\\Package\\BlockLibrary',
            'cards' => 'Vendor\\Package\\CardsBlock',
        ]);
});

it('overwrites the same builder block target without touching other targets', function (): void {
    $registry = new BuilderBlockRegistry;

    $registry->register('content', BuilderBlockTarget::AdminFilament, 'Vendor\\Package\\OriginalBlock');
    $registry->register('content', BuilderBlockTarget::FrontendBlade, 'vendor-package::blocks.content');
    $registry->register('content', BuilderBlockTarget::AdminFilament, 'Vendor\\Package\\ReplacementBlock');

    expect($registry->get('content', BuilderBlockTarget::AdminFilament))->toBe('Vendor\\Package\\ReplacementBlock')
        ->and($registry->get('content', BuilderBlockTarget::FrontendBlade))->toBe('vendor-package::blocks.content');
});

it('rejects empty builder block registration values', function (): void {
    (new BuilderBlockRegistry)->register('', BuilderBlockTarget::AdminFilament, 'Vendor\\Package\\BlockLibrary');
})->throws(InvalidArgumentException::class, 'Builder block name cannot be empty.');

it('rejects empty builder block lookup values', function (): void {
    (new BuilderBlockRegistry)->get('content', ' ');
})->throws(InvalidArgumentException::class, 'Builder block target cannot be empty.');
