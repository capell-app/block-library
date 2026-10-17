<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\ListBlockDefinitionsAction;
use Capell\BlockLibrary\Actions\RegisterBlockDefinitionProviderAction;
use Capell\BlockLibrary\Actions\ResolveBlockDefinitionAction;
use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Data\AdminPreviewBlockViewReference;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockSettingDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Data\PublicBlockViewReference;
use Capell\BlockLibrary\Support\BlockRegistry;

it('registers typed content block definitions', function (): void {
    $registry = new BlockRegistry;
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
        defaults: ['alignment' => 'center'],
    );

    $registry->register($definition);

    expect($registry->has('marketing.hero'))->toBeTrue()
        ->and($registry->getOrFail('marketing.hero'))->toBe($definition)
        ->and($registry->forCategory('marketing'))->toBe(['marketing.hero' => $definition]);
});

it('keeps legacy definitions backwards compatible with a default variant and trusted views', function (): void {
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
    );

    expect($definition->defaultVariant->value())->toBe('default')
        ->and($definition->variantKeys())->toBe(['default'])
        ->and($definition->publicViewName())->toBe('vendor-package::blocks.marketing-hero')
        ->and($definition->previewViewName())->toBe('vendor-package::blocks.marketing-hero');
});

it('supports package-owned variant keys without requiring a global enum case', function (): void {
    $definition = new BlockDefinitionData(
        key: 'agency.proof',
        label: 'Proof block',
        description: 'Agency proof layout.',
        category: 'agency',
        view: 'vendor-package::blocks.proof',
        variants: [
            new BlockVariantData(BlockVariantKey::from('bento-proof-wall'), 'vendor-package::blocks.variants.bento_proof_wall'),
        ],
        settings: [
            new BlockSettingDefinitionData(
                key: 'cards_per_row',
                labelKey: 'vendor-package::blocks.settings.cards_per_row',
                type: 'integer',
                default: 3,
                allowedVariants: ['bento-proof-wall'],
            ),
        ],
    );

    expect($definition->supportsVariant('bento-proof-wall'))->toBeTrue()
        ->and($definition->settings[0]->allowedVariants)->toBe(['bento-proof-wall']);
});

it('rejects admin or filament views as public block views', function (): void {
    PublicBlockViewReference::from('capell-admin::filament.blocks.hero');
})->throws(InvalidArgumentException::class, 'cannot reference admin or Filament views');

it('keeps public and admin preview view references context separated', function (): void {
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
        publicView: PublicBlockViewReference::from('vendor-package::blocks.marketing-hero'),
        previewView: AdminPreviewBlockViewReference::from('vendor-package::admin.preview.marketing-hero'),
    );

    expect($definition->publicViewName())->toBe('vendor-package::blocks.marketing-hero')
        ->and($definition->previewViewName())->toBe('vendor-package::admin.preview.marketing-hero');
});

it('guards against duplicate block keys', function (): void {
    $registry = new BlockRegistry;
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
    );

    $registry->register($definition);
    $registry->register($definition);
})->throws(InvalidArgumentException::class, 'Content block [marketing.hero] is already registered.');

it('rejects incomplete block definitions', function (): void {
    new BlockDefinitionData(
        key: '',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
    );
})->throws(InvalidArgumentException::class, 'Block definition [key] must not be empty.');

it('registers definitions from providers through the action boundary', function (): void {
    $registry = new BlockRegistry;
    $provider = new class implements BlockDefinitionProvider
    {
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'editorial.quote',
                label: 'Editorial quote',
                description: 'A pull quote block.',
                category: 'editorial',
                view: 'vendor-package::blocks.quote',
            );
        }
    };

    RegisterBlockDefinitionProviderAction::run($registry, $provider);

    expect($registry->get('editorial.quote')?->view)->toBe('vendor-package::blocks.quote');
});

it('lists and resolves definitions from the container registry', function (): void {
    $registry = resolve(BlockRegistry::class);
    $registry->register(new BlockDefinitionData(
        key: 'shared.media',
        label: 'Shared media',
        description: 'A reusable media block.',
        category: 'media',
        view: 'vendor-package::blocks.media',
    ));

    expect(ListBlockDefinitionsAction::run())->toHaveKey('shared.media')
        ->and(ResolveBlockDefinitionAction::run('shared.media')->label)->toBe('Shared media');
});

it('keeps equivalent provider definitions idempotent after direct registration', function (): void {
    $registry = new BlockRegistry;
    $provider = new class implements BlockDefinitionProvider
    {
        #[Override]
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'section.content',
                label: 'Content',
                description: 'A content section.',
                category: 'content',
                view: 'vendor-package::sections.content',
                sourcePackage: 'content-sections',
            );
        }
    };
    foreach ($provider->definitions() as $definition) {
        $registry->register($definition);
    }

    $definitions = $registry->all();
    RegisterBlockDefinitionProviderAction::run($registry, $provider);
    RegisterBlockDefinitionProviderAction::run($registry, clone $provider);

    expect($registry->all())->toBe($definitions)
        ->and($registry->getOrFail('section.content')->label)->toBe('Content');
});

it('still rejects conflicting provider definitions for an existing block key', function (): void {
    $registry = new BlockRegistry;
    $registry->register(new BlockDefinitionData(
        key: 'section.content',
        label: 'Original content',
        description: 'The original section.',
        category: 'content',
        view: 'vendor-package::sections.original',
    ));
    $provider = new class implements BlockDefinitionProvider
    {
        #[Override]
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'section.content',
                label: 'Different content',
                description: 'A conflicting section.',
                category: 'content',
                view: 'vendor-package::sections.different',
            );
        }
    };

    expect(static function () use ($registry, $provider): void {
        RegisterBlockDefinitionProviderAction::run($registry, $provider);
    })
        ->toThrow(InvalidArgumentException::class, 'Content block [section.content] is already registered.')
        ->and($registry->getOrFail('section.content')->view)->toBe('vendor-package::sections.original');
});

it('rejects provider definitions whose numeric text differs without replacing the original', function (string $field): void {
    $registry = new BlockRegistry;
    $original = new BlockDefinitionData(
        key: 'section.content',
        label: '01',
        description: 'A content section.',
        category: 'content',
        view: 'vendor-package::sections.content',
        defaults: ['text' => '01'],
    );
    $registry->register($original);
    $conflicting = clone $original;
    if ($field === 'label') {
        $conflicting->label = '1';
    } else {
        $conflicting->defaults = ['text' => '1'];
    }

    $provider = new class($conflicting) implements BlockDefinitionProvider
    {
        public function __construct(private readonly BlockDefinitionData $definition) {}

        #[Override]
        public function definitions(): iterable
        {
            yield $this->definition;
        }
    };

    expect(static function () use ($registry, $provider): void {
        RegisterBlockDefinitionProviderAction::run($registry, $provider);
    })
        ->toThrow(InvalidArgumentException::class, 'Content block [section.content] is already registered.')
        ->and($registry->getOrFail('section.content'))->toBe($original)
        ->and($original->label)->toBe('01')
        ->and($original->defaults)->toBe(['text' => '01']);
})->with(['label', 'default text']);

it('registers tagged providers when the registry is resolved', function (): void {
    app()->bind('test.content-block-provider', static fn (): BlockDefinitionProvider => new class implements BlockDefinitionProvider
    {
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'commerce.price-card',
                label: 'Price card',
                description: 'A pricing card block.',
                category: 'commerce',
                view: 'vendor-package::blocks.price-card',
            );
        }
    });

    app()->tag(['test.content-block-provider'], BlockDefinitionProvider::TAG);

    expect(resolve(BlockRegistry::class)->get('commerce.price-card')?->label)->toBe('Price card');
});
