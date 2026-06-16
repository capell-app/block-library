<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\BuilderBlocks;

use Capell\BlockLibrary\Contracts\FilamentBuilderBlock;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

abstract class AbstractCatalogBuilderBlock implements FilamentBuilderBlock
{
    protected const string KEY = '';

    public static function getBuilderBlockName(): string
    {
        return static::KEY;
    }

    public static function make(): Block
    {
        return Block::make(static::KEY)
            ->label(DefaultBlockCatalog::label(static::KEY))
            ->icon(DefaultBlockCatalog::icon(static::KEY))
            ->schema(static::schema());
    }

    /**
     * @return array<int, mixed>
     */
    protected static function schema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('capell-block-library::blocks.fields.title'))
                ->maxLength(160),
            Textarea::make('summary')
                ->label(__('capell-block-library::blocks.fields.summary'))
                ->rows(4),
            ...static::metaSchema(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function metaSchema(): array
    {
        return match (static::KEY) {
            'accordion' => [
                static::itemsRepeater('items', [
                    TextInput::make('heading')->label(__('capell-block-library::blocks.fields.heading'))->required(),
                    Textarea::make('content')->label(__('capell-block-library::blocks.fields.content'))->rows(4),
                ]),
                Toggle::make('first_open')->label(__('capell-block-library::blocks.fields.first_open')),
            ],
            'call_to_action' => [
                Select::make('alignment')
                    ->label(__('capell-block-library::blocks.fields.alignment'))
                    ->options(static::alignmentOptions())
                    ->default('center'),
                static::actionsRepeater(),
            ],
            'comparison' => [
                static::itemsRepeater('columns', [
                    TextInput::make('heading')->label(__('capell-block-library::blocks.fields.heading'))->required(),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                    Toggle::make('highlighted')->label(__('capell-block-library::blocks.fields.highlighted')),
                ]),
                static::itemsRepeater('rows', [
                    TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
                    TextInput::make('values')->label(__('capell-block-library::blocks.fields.values')),
                ]),
            ],
            'content' => [],
            'counter' => [
                static::itemsRepeater('counters', [
                    TextInput::make('value')->label(__('capell-block-library::blocks.fields.value'))->required(),
                    TextInput::make('prefix')->label(__('capell-block-library::blocks.fields.prefix')),
                    TextInput::make('suffix')->label(__('capell-block-library::blocks.fields.suffix')),
                    TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                ]),
            ],
            'divider' => [
                Select::make('style')
                    ->label(__('capell-block-library::blocks.fields.style'))
                    ->options([
                        'line' => __('capell-block-library::blocks.fields.style_line'),
                        'dots' => __('capell-block-library::blocks.fields.style_dots'),
                    ])
                    ->default('line'),
            ],
            'faq' => [
                static::itemsRepeater('questions', [
                    TextInput::make('question')->label(__('capell-block-library::blocks.fields.question'))->required(),
                    Textarea::make('answer')->label(__('capell-block-library::blocks.fields.answer'))->rows(4),
                ]),
                Toggle::make('first_open')->label(__('capell-block-library::blocks.fields.first_open')),
            ],
            'features' => [
                static::columnsSelect(),
                static::itemsRepeater('features', [
                    TextInput::make('heading')->label(__('capell-block-library::blocks.fields.heading'))->required(),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                    TextInput::make('url')->label(__('capell-block-library::blocks.fields.url')),
                ]),
            ],
            'hero' => [
                Select::make('alignment')
                    ->label(__('capell-block-library::blocks.fields.alignment'))
                    ->options(static::alignmentOptions())
                    ->default('center'),
                TextInput::make('url')->label(__('capell-block-library::blocks.fields.url')),
            ],
            'logos' => [
                static::columnsSelect(default: '4'),
                static::itemsRepeater('logos', [
                    TextInput::make('name')->label(__('capell-block-library::blocks.fields.name'))->required(),
                    TextInput::make('url')->label(__('capell-block-library::blocks.fields.url')),
                ]),
            ],
            'pricing' => [
                static::itemsRepeater('plans', [
                    TextInput::make('name')->label(__('capell-block-library::blocks.fields.name'))->required(),
                    TextInput::make('price')->label(__('capell-block-library::blocks.fields.price'))->required(),
                    TextInput::make('period')->label(__('capell-block-library::blocks.fields.period')),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                    Textarea::make('features')->label(__('capell-block-library::blocks.fields.features'))->rows(5),
                    Toggle::make('highlighted')->label(__('capell-block-library::blocks.fields.highlighted')),
                    TextInput::make('action_label')->label(__('capell-block-library::blocks.fields.action_label')),
                    TextInput::make('action_url')->label(__('capell-block-library::blocks.fields.action_url')),
                ]),
            ],
            'stats' => [
                static::columnsSelect(default: '4'),
                static::itemsRepeater('stats', [
                    TextInput::make('value')->label(__('capell-block-library::blocks.fields.value'))->required(),
                    TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                ]),
            ],
            'table' => [
                TextInput::make('caption')->label(__('capell-block-library::blocks.fields.caption')),
                static::itemsRepeater('headers', [
                    TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
                ]),
                static::itemsRepeater('rows', [
                    TextInput::make('cells')->label(__('capell-block-library::blocks.fields.cells'))->required(),
                ]),
            ],
            'tabs' => [
                static::itemsRepeater('tabs', [
                    TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
                    Textarea::make('content')->label(__('capell-block-library::blocks.fields.content'))->rows(4),
                ]),
            ],
            'team' => [
                static::columnsSelect(),
                static::itemsRepeater('members', [
                    TextInput::make('name')->label(__('capell-block-library::blocks.fields.name'))->required(),
                    TextInput::make('role')->label(__('capell-block-library::blocks.fields.role')),
                    Textarea::make('bio')->label(__('capell-block-library::blocks.fields.bio'))->rows(3),
                    TextInput::make('url')->label(__('capell-block-library::blocks.fields.url')),
                ]),
            ],
            'testimonial' => [
                Textarea::make('quote')->label(__('capell-block-library::blocks.fields.quote'))->rows(4),
                TextInput::make('author')->label(__('capell-block-library::blocks.fields.author')),
                TextInput::make('role')->label(__('capell-block-library::blocks.fields.role')),
            ],
            'timeline' => [
                static::itemsRepeater('milestones', [
                    TextInput::make('date')->label(__('capell-block-library::blocks.fields.date')),
                    TextInput::make('heading')->label(__('capell-block-library::blocks.fields.heading'))->required(),
                    Textarea::make('description')->label(__('capell-block-library::blocks.fields.description'))->rows(3),
                ]),
            ],
            default => [],
        };
    }

    /**
     * @param  array<int, mixed>  $schema
     */
    protected static function itemsRepeater(string $name, array $schema): Repeater
    {
        return Repeater::make($name)
            ->label(__('capell-block-library::blocks.fields.' . $name))
            ->schema($schema)
            ->reorderable()
            ->collapsible();
    }

    protected static function columnsSelect(string $default = '3'): Select
    {
        return Select::make('columns')
            ->label(__('capell-block-library::blocks.fields.columns'))
            ->options([
                '2' => __('capell-block-library::blocks.fields.columns_2'),
                '3' => __('capell-block-library::blocks.fields.columns_3'),
                '4' => __('capell-block-library::blocks.fields.columns_4'),
            ])
            ->default($default);
    }

    /**
     * @return array<string, string>
     */
    protected static function alignmentOptions(): array
    {
        return [
            'start' => __('capell-block-library::blocks.fields.align_start'),
            'center' => __('capell-block-library::blocks.fields.align_center'),
            'end' => __('capell-block-library::blocks.fields.align_end'),
        ];
    }

    protected static function actionsRepeater(): Repeater
    {
        return static::itemsRepeater('actions', [
            TextInput::make('label')->label(__('capell-block-library::blocks.fields.label'))->required(),
            TextInput::make('url')->label(__('capell-block-library::blocks.fields.url')),
        ]);
    }
}
