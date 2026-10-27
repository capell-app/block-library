<?php

declare(strict_types=1);

use Capell\Admin\Testing\Filament\ReadsRawSchemaComponents;
use Capell\BlockLibrary\Filament\BuilderBlocks\AccordionBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\CallToActionBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\ComparisonBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\CounterBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\DividerBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\FaqBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\FeaturesBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\HeroBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\LogosBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\PricingBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\StatsBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\TableBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\TeamBlock;
use Capell\BlockLibrary\Filament\BuilderBlocks\TimelineBlock;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\Assert;

it('exposes translated helpers for the listed form fields', function (Closure $buildSchema, array $expected): void {
    $translator = resolve(Translator::class);
    $lines = [];

    foreach ($expected as $key) {
        $translationKey = 'capell-block-library::helpers.' . $key;
        $translation = $translator->get($translationKey, locale: 'en');
        Assert::assertIsString($translation);
        Assert::assertNotSame($translationKey, $translation);
        Assert::assertNotSame('', trim($translation));
        $lines['helpers.' . $key] = 'Translated guidance: ' . $key;
    }

    $translator->addLines($lines, 'helper-test', 'capell-block-library');
    $originalLocale = $translator->getLocale();
    $translator->setLocale('helper-test');

    try {
        $schema = $buildSchema(Schema::make(new SchemaStateHarness));
        $fields = flattenBlockLibraryHelperFields($schema->getComponents(withHidden: true));

        foreach ($expected as $name => $key) {
            $matchingFields = array_filter($fields, static fn (Field $field): bool => $field->getName() === $name);
            Assert::assertNotEmpty($matchingFields, "Missing field [{$name}].");

            foreach ($matchingFields as $field) {
                // Mount nested repeater fields without requiring a saved repeater item.
                Schema::make(new SchemaStateHarness)->components([$field])->getComponents(withHidden: true);
                $helpers = $field->getChildSchema('below_content')?->getComponents(withHidden: true) ?? [];
                Assert::assertCount(1, $helpers, "Missing helper for [{$name}].");
                Assert::assertInstanceOf(Text::class, $helpers[0]);
                Assert::assertSame('Translated guidance: ' . $key, $helpers[0]->getContent());
            }
        }
    } finally {
        $translator->setLocale($originalLocale);
    }
})->with([
    'AccordionBlock' => [
        static fn (Schema $schema): Schema => $schema->components([AccordionBlock::make()]),
        [
            'first_open' => 'AbstractCatalogBuilderBlock.first_open',
        ],
    ],
    'CallToActionBlock' => [
        static fn (Schema $schema): Schema => $schema->components([CallToActionBlock::make()]),
        [
            'alignment' => 'AbstractCatalogBuilderBlock.alignment',
        ],
    ],
    'ComparisonBlock' => [
        static fn (Schema $schema): Schema => $schema->components([ComparisonBlock::make()]),
        [
            'highlighted' => 'AbstractCatalogBuilderBlock.highlighted',
            'values' => 'AbstractCatalogBuilderBlock.values',
        ],
    ],
    'CounterBlock' => [
        static fn (Schema $schema): Schema => $schema->components([CounterBlock::make()]),
        [
            'prefix' => 'AbstractCatalogBuilderBlock.prefix',
            'suffix' => 'AbstractCatalogBuilderBlock.suffix',
        ],
    ],
    'DividerBlock' => [
        static fn (Schema $schema): Schema => $schema->components([DividerBlock::make()]),
        [
            'style' => 'AbstractCatalogBuilderBlock.style',
        ],
    ],
    'FaqBlock' => [
        static fn (Schema $schema): Schema => $schema->components([FaqBlock::make()]),
        [
            'first_open' => 'AbstractCatalogBuilderBlock.first_open',
        ],
    ],
    'FeaturesBlock' => [
        static fn (Schema $schema): Schema => $schema->components([FeaturesBlock::make()]),
        [
            'columns' => 'AbstractCatalogBuilderBlock.columns',
        ],
    ],
    'HeroBlock' => [
        static fn (Schema $schema): Schema => $schema->components([HeroBlock::make()]),
        [
            'alignment' => 'AbstractCatalogBuilderBlock.alignment',
        ],
    ],
    'LogosBlock' => [
        static fn (Schema $schema): Schema => $schema->components([LogosBlock::make()]),
        [
            'columns' => 'AbstractCatalogBuilderBlock.columns',
            'image' => 'BuilderBlockMediaFields.image',
        ],
    ],
    'PricingBlock' => [
        static fn (Schema $schema): Schema => $schema->components([PricingBlock::make()]),
        [
            'price' => 'AbstractCatalogBuilderBlock.price',
            'period' => 'AbstractCatalogBuilderBlock.period',
            'features' => 'AbstractCatalogBuilderBlock.features',
            'highlighted' => 'AbstractCatalogBuilderBlock.highlighted',
            'action_url' => 'AbstractCatalogBuilderBlock.action_url',
        ],
    ],
    'StatsBlock' => [
        static fn (Schema $schema): Schema => $schema->components([StatsBlock::make()]),
        [
            'columns' => 'AbstractCatalogBuilderBlock.columns',
        ],
    ],
    'TableBlock' => [
        static fn (Schema $schema): Schema => $schema->components([TableBlock::make()]),
        [
            'cells' => 'AbstractCatalogBuilderBlock.cells',
        ],
    ],
    'TeamBlock' => [
        static fn (Schema $schema): Schema => $schema->components([TeamBlock::make()]),
        [
            'columns' => 'AbstractCatalogBuilderBlock.columns',
            'image' => 'BuilderBlockMediaFields.image',
        ],
    ],
    'TimelineBlock' => [
        static fn (Schema $schema): Schema => $schema->components([TimelineBlock::make()]),
        [
            'date' => 'AbstractCatalogBuilderBlock.date',
        ],
    ],
]);

/**
 * @param  array<array-key, mixed>  $components
 * @return list<Field>
 */
function flattenBlockLibraryHelperFields(array $components): array
{
    $fields = [];

    foreach ($components as $component) {
        if (! $component instanceof Component) {
            continue;
        }

        if ($component instanceof Field) {
            $fields[] = $component;
        }

        array_push($fields, ...flattenBlockLibraryHelperFields(ReadsRawSchemaComponents::childComponents($component)));
    }

    return $fields;
}
