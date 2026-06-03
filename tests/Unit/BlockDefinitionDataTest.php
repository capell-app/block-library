<?php

declare(strict_types=1);

use Capell\BlockLibrary\Contracts\BlockDemoContentProvider;
use Capell\BlockLibrary\Contracts\BlockFixtureProvider;
use Capell\BlockLibrary\Data\AdminPreviewBlockViewReference;
use Capell\BlockLibrary\Data\BlockAccessibilityContractData;
use Capell\BlockLibrary\Data\BlockCompatibilityData;
use Capell\BlockLibrary\Data\BlockContentContractData;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockFixtureData;
use Capell\BlockLibrary\Data\BlockScreenshotData;
use Capell\BlockLibrary\Data\BlockSettingDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Data\PublicBlockPresentationData;
use Capell\BlockLibrary\Data\PublicBlockViewReference;
use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Capell\BlockLibrary\Support\NullBlockDefinition;

it('serializes public presentation and accessibility contracts', function (): void {
    $presentation = new PublicBlockPresentationData(
        variant: 'split-feature',
        spacing: 'tight',
        background: 'muted',
        mediaPosition: 'left',
        cardsPerRow: 2,
        showCta: false,
        headingWidth: 'wide',
        anchorId: 'feature-block',
    );

    $accessibility = new BlockAccessibilityContractData(
        semanticRules: ['single-h1'],
        keyboardRules: ['focusable-actions'],
        contrastPairs: ['foreground-background'],
        mediaRules: ['alt-text'],
    );

    expect($presentation->toArray())->toBe([
        'variant' => 'split-feature',
        'spacing' => 'tight',
        'background' => 'muted',
        'mediaPosition' => 'left',
        'cardsPerRow' => 2,
        'showCta' => false,
        'headingWidth' => 'wide',
        'anchorId' => 'feature-block',
    ])
        ->and($accessibility->toArray())->toBe([
            'semanticRules' => ['single-h1'],
            'keyboardRules' => ['focusable-actions'],
            'contrastPairs' => ['foreground-background'],
            'mediaRules' => ['alt-text'],
        ]);
});

it('normalizes compatibility and validates variant defaults', function (): void {
    $compatibility = new BlockCompatibilityData(
        themeKeys: ['theme-saas'],
        unsupportedThemeKeys: ['theme-legacy'],
        requiredPackages: ['capell-app/media-library'],
        requiresAccessibleTokenPairs: false,
    );

    expect($compatibility->supportsTheme(null))->toBeTrue()
        ->and($compatibility->supportsTheme(''))->toBeTrue()
        ->and($compatibility->supportsTheme('theme-saas'))->toBeTrue()
        ->and($compatibility->supportsTheme('theme-agency'))->toBeFalse()
        ->and($compatibility->supportsTheme('theme-legacy'))->toBeFalse()
        ->and($compatibility->requiredPackages)->toBe(['capell-app/media-library'])
        ->and($compatibility->requiresAccessibleTokenPairs)->toBeFalse();

    new BlockDefinitionData(
        key: 'marketing.proof',
        label: 'Proof',
        description: 'Proof block.',
        category: 'marketing',
        view: 'vendor-package::blocks.proof',
        variants: [
            new BlockVariantData(BlockVariantKey::from('logo-wall'), 'vendor-package::blocks.variants.logo_wall'),
        ],
        defaultVariant: BlockVariantKey::from('missing-variant'),
    );
})->throws(InvalidArgumentException::class, 'Default block variant [missing-variant] is not registered');

it('validates references fixtures settings and screenshots', function (): void {
    expect(AdminPreviewBlockViewReference::from('vendor-package::admin.preview')->value())->toBe('vendor-package::admin.preview')
        ->and(PublicBlockViewReference::from('vendor-package::blocks.public')->value())->toBe('vendor-package::blocks.public');

    new BlockFixtureData(key: '', label: 'Hero', payload: []);
})->throws(InvalidArgumentException::class, 'Block fixture key cannot be empty.');

it('rejects invalid settings and screenshots', function (): void {
    expect(fn (): BlockSettingDefinitionData => new BlockSettingDefinitionData(
        key: 'cards',
        labelKey: '',
        type: 'integer',
    ))->toThrow(InvalidArgumentException::class, 'Block setting [labelKey] cannot be empty.');

    expect(fn (): BlockScreenshotData => new BlockScreenshotData(
        path: '/screenshots/hero.png',
        alt: '',
        caption: 'Hero screenshot',
    ))->toThrow(InvalidArgumentException::class, 'Block screenshot [alt] cannot be empty.');
});

it('requires fixture and demo providers to implement their contracts', function (): void {
    expect(fn (): BlockDefinitionData => new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        fixtureProvider: stdClass::class,
    ))->toThrow(InvalidArgumentException::class, 'must implement ' . BlockFixtureProvider::class);

    expect(fn (): BlockDefinitionData => new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        demoContentProvider: stdClass::class,
    ))->toThrow(InvalidArgumentException::class, 'must implement ' . BlockDemoContentProvider::class);
});

it('builds a safe fallback block definition', function (): void {
    $definition = NullBlockDefinition::make('unknown.block');

    expect($definition->key)->toBe('unknown.block')
        ->and($definition->safeForPublicOutput)->toBeTrue()
        ->and($definition->sourcePackage)->toBe('capell-app/block-library')
        ->and($definition->publicViewName())->toBe('capell-block-library::blocks.fallback')
        ->and($definition->variantKeys())->toBe(['default']);
});

it('registers package metadata and health compatibility', function (): void {
    expect(BlockLibraryServiceProvider::$name)->toBe('capell-block-library')
        ->and(BlockLibraryServiceProvider::$packageName)->toBe('capell-app/block-library')
        ->and(BlockLibraryHealthCheck::compatibleCapellApiVersion())->toBe('^4.0');
});

it('accepts valid custom providers', function (): void {
    $fixtureProvider = new class implements BlockFixtureProvider
    {
        public function fixtures(BlockDefinitionData $definition): iterable
        {
            yield new BlockFixtureData('hero.default', 'Default hero', ['title' => 'Hello']);
        }
    };

    $demoContentProvider = new class implements BlockDemoContentProvider
    {
        public function demoContent(BlockDefinitionData $definition): array
        {
            return ['title' => 'Demo title'];
        }
    };

    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        contentContract: new BlockContentContractData(
            requiredFields: ['title'],
            optionalFields: ['subtitle'],
            maxItems: 3,
            imageRatios: ['16:9'],
            requiresCta: true,
            allowEmptyCta: false,
            accessibilityRules: ['alt-text'],
        ),
        fixtureProvider: $fixtureProvider::class,
        demoContentProvider: $demoContentProvider::class,
    );

    expect($definition->fixtureProvider)->toBe($fixtureProvider::class)
        ->and($definition->demoContentProvider)->toBe($demoContentProvider::class)
        ->and($definition->contentContract->requiredFields)->toBe(['title'])
        ->and($definition->contentContract->requiresCta)->toBeTrue();
});
